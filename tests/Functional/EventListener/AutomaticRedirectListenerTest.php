<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslation;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class AutomaticRedirectListenerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private EventDispatcherInterface $eventDispatcher;

    private RedirectRepositoryInterface $redirectRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $entityManager = $container->get('doctrine.orm.default_entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        \assert($eventDispatcher instanceof EventDispatcherInterface);
        $this->eventDispatcher = $eventDispatcher;

        $redirectRepository = $container->get('setono_sylius_redirect.repository.redirect');
        \assert($redirectRepository instanceof RedirectRepositoryInterface);
        $this->redirectRepository = $redirectRepository;
    }

    public function test_it_creates_a_redirect_when_a_product_translation_slug_changes(): void
    {
        $product = $this->persistProductWithSlug('en_US', 'old-product-slug');

        /** @var ProductTranslation $translation */
        $translation = $product->getTranslation('en_US');
        $translation->setSlug('new-product-slug');

        $this->eventDispatcher->dispatch(new GenericEvent($product), 'sylius.product.pre_update');
        $this->entityManager->flush();

        $redirect = $this->redirectRepository->findOneBySource('/en_US/products/old-product-slug');

        self::assertNotNull($redirect, 'A Redirect row should have been persisted.');
        self::assertSame('/en_US/products/new-product-slug', $redirect->getDestination());
        self::assertTrue($redirect->isPermanent());
        self::assertTrue($redirect->isOnly404());
        self::assertTrue($redirect->isEnabled());
        self::assertCount(0, $redirect->getChannels());
    }

    public function test_it_creates_one_redirect_per_changed_locale(): void
    {
        $product = $this->persistProduct([
            'en_US' => 'old-en-slug',
            'de_DE' => 'old-de-slug',
        ]);

        /** @var ProductTranslation $en */
        $en = $product->getTranslation('en_US');
        $en->setSlug('new-en-slug');

        /** @var ProductTranslation $de */
        $de = $product->getTranslation('de_DE');
        $de->setSlug('new-de-slug');

        $this->eventDispatcher->dispatch(new GenericEvent($product), 'sylius.product.pre_update');
        $this->entityManager->flush();

        $enRedirect = $this->redirectRepository->findOneBySource('/en_US/products/old-en-slug');
        self::assertNotNull($enRedirect);
        self::assertSame('/en_US/products/new-en-slug', $enRedirect->getDestination());

        $deRedirect = $this->redirectRepository->findOneBySource('/de_DE/products/old-de-slug');
        self::assertNotNull($deRedirect);
        self::assertSame('/de_DE/products/new-de-slug', $deRedirect->getDestination());
    }

    public function test_it_does_not_create_a_redirect_when_no_slug_changed(): void
    {
        $product = $this->persistProductWithSlug('en_US', 'unchanged-slug');

        $this->eventDispatcher->dispatch(new GenericEvent($product), 'sylius.product.pre_update');
        $this->entityManager->flush();

        self::assertNull(
            $this->redirectRepository->findOneBySource('/en_US/products/unchanged-slug'),
            'No Redirect row should have been persisted when the slug did not change.',
        );
    }

    public function test_it_does_not_create_a_redirect_for_a_resource_alias_that_is_not_enabled(): void
    {
        $product = $this->persistProductWithSlug('en_US', 'admin-slug');

        /** @var ProductTranslation $translation */
        $translation = $product->getTranslation('en_US');
        $translation->setSlug('admin-slug-renamed');

        // sylius.administrator is not in automatic_redirects (and would not be slug-aware anyway).
        // Dispatching its pre_update event must not trigger redirect creation for the product.
        $this->eventDispatcher->dispatch(new GenericEvent($product), 'sylius.administrator.pre_update');
        $this->entityManager->flush();

        self::assertNull(
            $this->redirectRepository->findOneBySource('/en_US/products/admin-slug'),
            'No Redirect should be created when the dispatched event alias is not configured.',
        );
    }

    /**
     * @param array<string, string> $translations Map of locale code → slug
     */
    private function persistProduct(array $translations): ProductInterface
    {
        $product = new Product();
        $product->setCode(uniqid('product_', true));

        $first = array_key_first($translations);
        \assert(is_string($first));
        $product->setCurrentLocale($first);
        $product->setFallbackLocale($first);

        foreach ($translations as $locale => $slug) {
            $translation = new ProductTranslation();
            $translation->setLocale($locale);
            $translation->setName('Product ' . $locale);
            $translation->setSlug($slug);
            $product->addTranslation($translation);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        // Refresh to make sure the UoW's original-entity-data reflects the persisted slug
        // before any in-memory mutation simulates the admin form binding new values.
        $this->entityManager->refresh($product);

        return $product;
    }

    private function persistProductWithSlug(string $locale, string $slug): ProductInterface
    {
        return $this->persistProduct([$locale => $slug]);
    }
}
