<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Setono\SyliusRedirectPlugin\EventListener\AutomaticRedirectListener;
use Setono\SyliusRedirectPlugin\Exception\SlugUpdateHandlerValidationException;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Setono\SyliusRedirectPlugin\Model\Redirect;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolverInterface;
use stdClass;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Product\Model\ProductTranslation;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AutomaticRedirectListenerTest extends TestCase
{
    use ProphecyTrait;

    public function test_it_returns_quietly_when_subject_is_not_translatable(): void
    {
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Argument::any())->shouldNotBeCalled();

        $factory = $this->prophesize(RedirectFactoryInterface::class);
        $factory->createNewWithValues(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->createListener(
            managerRegistry: $managerRegistry,
            factory: $factory,
        );

        $listener->onPreUpdate(new GenericEvent(new stdClass()));

        $this->addToAssertionCount(1);
    }

    public function test_it_creates_one_redirect_when_a_single_locale_slug_changes(): void
    {
        $product = new Product();
        $translation = $this->translationFor($product, 'en_US', newSlug: 'new-slug');

        $redirect = new Redirect();

        $manager = $this->prophesizeManagerFor([[$translation, ['slug' => 'old-slug']]]);

        $factory = $this->prophesize(RedirectFactoryInterface::class);
        $factory->createNewWithValues('/old-url', '/new-url', true, true, [])
            ->shouldBeCalledOnce()
            ->willReturn($redirect)
        ;

        $resolver = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $resolver->resolve($product, 'old-slug', 'en_US')->willReturn('/old-url');
        $resolver->resolve($product, 'new-slug', 'en_US')->willReturn('/new-url');

        $finder = $this->prophesize(RemovableRedirectFinderInterface::class);
        $finder->findRedirectsTargetedBy($redirect)->willReturn(new ArrayCollection());

        $validator = $this->prophesize(ValidatorInterface::class);
        $validator->validate($redirect, null, ['sylius', 'setono_sylius_redirect'])
            ->willReturn(new ConstraintViolationList())
        ;

        $manager->persist($redirect)->shouldBeCalledOnce();
        $manager->remove(Argument::any())->shouldNotBeCalled();

        $listener = $this->createListener(
            managerRegistry: $this->managerRegistryReturning($manager),
            factory: $factory,
            urlResolver: $resolver,
            finder: $finder,
            validator: $validator,
        );

        $listener->onPreUpdate(new GenericEvent($product));
    }

    public function test_it_creates_one_redirect_per_changed_locale(): void
    {
        $product = new Product();
        $en = $this->translationFor($product, 'en_US', newSlug: 'new-en');
        $de = $this->translationFor($product, 'de_DE', newSlug: 'new-de');

        $redirectEn = new Redirect();
        $redirectEn->setSource('/old-en-url');
        $redirectEn->setDestination('/new-en-url');
        $redirectDe = new Redirect();
        $redirectDe->setSource('/old-de-url');
        $redirectDe->setDestination('/new-de-url');

        $manager = $this->prophesizeManagerFor([
            [$en, ['slug' => 'old-en']],
            [$de, ['slug' => 'old-de']],
        ]);

        $factory = $this->prophesize(RedirectFactoryInterface::class);
        $factory->createNewWithValues('/old-en-url', '/new-en-url', true, true, [])->willReturn($redirectEn);
        $factory->createNewWithValues('/old-de-url', '/new-de-url', true, true, [])->willReturn($redirectDe);

        $resolver = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $resolver->resolve($product, 'old-en', 'en_US')->willReturn('/old-en-url');
        $resolver->resolve($product, 'new-en', 'en_US')->willReturn('/new-en-url');
        $resolver->resolve($product, 'old-de', 'de_DE')->willReturn('/old-de-url');
        $resolver->resolve($product, 'new-de', 'de_DE')->willReturn('/new-de-url');

        $finder = $this->prophesize(RemovableRedirectFinderInterface::class);
        $finder->findRedirectsTargetedBy(Argument::type(RedirectInterface::class))->willReturn(new ArrayCollection());

        $validator = $this->prophesize(ValidatorInterface::class);
        $validator->validate(Argument::type(RedirectInterface::class), null, Argument::any())
            ->willReturn(new ConstraintViolationList())
        ;

        $manager->persist($redirectEn)->shouldBeCalledOnce();
        $manager->persist($redirectDe)->shouldBeCalledOnce();

        $listener = $this->createListener(
            managerRegistry: $this->managerRegistryReturning($manager),
            factory: $factory,
            urlResolver: $resolver,
            finder: $finder,
            validator: $validator,
        );

        $listener->onPreUpdate(new GenericEvent($product));
    }

    public function test_it_skips_translations_whose_slug_did_not_change(): void
    {
        $product = new Product();
        $translation = $this->translationFor($product, 'en_US', newSlug: 'same-slug');

        $manager = $this->prophesizeManagerFor([[$translation, ['slug' => 'same-slug']]]);

        $factory = $this->prophesize(RedirectFactoryInterface::class);
        $factory->createNewWithValues(Argument::cetera())->shouldNotBeCalled();

        $manager->persist(Argument::any())->shouldNotBeCalled();

        $listener = $this->createListener(
            managerRegistry: $this->managerRegistryReturning($manager),
            factory: $factory,
        );

        $listener->onPreUpdate(new GenericEvent($product));
    }

    public function test_it_skips_translations_with_no_unit_of_work_data(): void
    {
        $product = new Product();
        $translation = $this->translationFor($product, 'en_US', newSlug: 'new-slug');

        $manager = $this->prophesizeManagerFor([[$translation, []]]);

        $factory = $this->prophesize(RedirectFactoryInterface::class);
        $factory->createNewWithValues(Argument::cetera())->shouldNotBeCalled();

        $manager->persist(Argument::any())->shouldNotBeCalled();

        $listener = $this->createListener(
            managerRegistry: $this->managerRegistryReturning($manager),
            factory: $factory,
        );

        $listener->onPreUpdate(new GenericEvent($product));
    }

    public function test_it_logs_an_error_and_skips_when_the_translation_class_has_no_slug_field(): void
    {
        $product = new Product();
        $translation = $this->translationFor($product, 'en_US', newSlug: 'new-slug');

        $manager = $this->prophesize(EntityManagerInterface::class);
        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->hasField('slug')->willReturn(false);
        $manager->getClassMetadata($translation::class)->willReturn($metadata->reveal());
        $manager->getUnitOfWork()->shouldNotBeCalled();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::containingString($translation::class))->shouldBeCalledOnce();

        $factory = $this->prophesize(RedirectFactoryInterface::class);
        $factory->createNewWithValues(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->createListener(
            managerRegistry: $this->managerRegistryReturning($manager),
            factory: $factory,
        );
        $listener->setLogger($logger->reveal());

        $listener->onPreUpdate(new GenericEvent($product));
    }

    public function test_it_removes_redundant_redirects_before_persisting_the_new_one(): void
    {
        $product = new Product();
        $translation = $this->translationFor($product, 'en_US', newSlug: 'new-slug');

        $redirect = new Redirect();
        $stale = new Redirect();

        $manager = $this->prophesizeManagerFor([[$translation, ['slug' => 'old-slug']]]);

        $factory = $this->prophesize(RedirectFactoryInterface::class);
        $factory->createNewWithValues(Argument::cetera())->willReturn($redirect);

        $resolver = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $resolver->resolve(Argument::cetera())->willReturn('/u');

        $finder = $this->prophesize(RemovableRedirectFinderInterface::class);
        $finder->findRedirectsTargetedBy($redirect)->willReturn(new ArrayCollection([$stale]));

        $validator = $this->prophesize(ValidatorInterface::class);
        $validator->validate(Argument::cetera())->willReturn(new ConstraintViolationList());

        $manager->remove($stale)->shouldBeCalledOnce();
        $manager->persist($redirect)->shouldBeCalledOnce();

        $listener = $this->createListener(
            managerRegistry: $this->managerRegistryReturning($manager),
            factory: $factory,
            urlResolver: $resolver,
            finder: $finder,
            validator: $validator,
        );

        $listener->onPreUpdate(new GenericEvent($product));
    }

    public function test_it_throws_validation_exception_and_does_not_persist_when_the_redirect_is_invalid(): void
    {
        $product = new Product();
        $translation = $this->translationFor($product, 'en_US', newSlug: 'new-slug');

        $redirect = new Redirect();

        $manager = $this->prophesizeManagerFor([[$translation, ['slug' => 'old-slug']]]);

        $factory = $this->prophesize(RedirectFactoryInterface::class);
        $factory->createNewWithValues(Argument::cetera())->willReturn($redirect);

        $resolver = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $resolver->resolve(Argument::cetera())->willReturn('/u');

        $finder = $this->prophesize(RemovableRedirectFinderInterface::class);
        $finder->findRedirectsTargetedBy($redirect)->willReturn(new ArrayCollection());

        $violations = new ConstraintViolationList();
        $violations->add($this->prophesize(ConstraintViolationInterface::class)->reveal());

        $validator = $this->prophesize(ValidatorInterface::class);
        $validator->validate(Argument::cetera())->willReturn($violations);

        $manager->persist(Argument::any())->shouldNotBeCalled();

        $listener = $this->createListener(
            managerRegistry: $this->managerRegistryReturning($manager),
            factory: $factory,
            urlResolver: $resolver,
            finder: $finder,
            validator: $validator,
        );

        $this->expectException(SlugUpdateHandlerValidationException::class);

        $listener->onPreUpdate(new GenericEvent($product));
    }

    /**
     * @param ObjectProphecy<ManagerRegistry>|null $managerRegistry
     * @param ObjectProphecy<RedirectFactoryInterface>|null $factory
     * @param ObjectProphecy<AutomaticRedirectUrlResolverInterface>|null $urlResolver
     * @param ObjectProphecy<RemovableRedirectFinderInterface>|null $finder
     * @param ObjectProphecy<ValidatorInterface>|null $validator
     */
    private function createListener(
        ?ObjectProphecy $managerRegistry = null,
        ?ObjectProphecy $factory = null,
        ?ObjectProphecy $urlResolver = null,
        ?ObjectProphecy $finder = null,
        ?ObjectProphecy $validator = null,
    ): AutomaticRedirectListener {
        return new AutomaticRedirectListener(
            ($managerRegistry ?? $this->prophesize(ManagerRegistry::class))->reveal(),
            ($factory ?? $this->prophesize(RedirectFactoryInterface::class))->reveal(),
            ($urlResolver ?? $this->prophesize(AutomaticRedirectUrlResolverInterface::class))->reveal(),
            ($finder ?? $this->prophesize(RemovableRedirectFinderInterface::class))->reveal(),
            ($validator ?? $this->prophesize(ValidatorInterface::class))->reveal(),
            ['sylius', 'setono_sylius_redirect'],
        );
    }

    private function translationFor(Product $product, string $locale, string $newSlug): ProductTranslation
    {
        $translation = new ProductTranslation();
        $translation->setLocale($locale);
        $translation->setSlug($newSlug);
        $product->addTranslation($translation);

        return $translation;
    }

    /**
     * @param list<array{0: object, 1: array<string, mixed>}> $pairs
     *
     * @return ObjectProphecy<EntityManagerInterface>
     */
    private function prophesizeManagerFor(array $pairs): ObjectProphecy
    {
        $manager = $this->prophesize(EntityManagerInterface::class);

        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->hasField('slug')->willReturn(true);

        $unitOfWork = $this->prophesize(UnitOfWork::class);

        foreach ($pairs as [$translation, $originals]) {
            $manager->getClassMetadata($translation::class)->willReturn($metadata->reveal());
            $unitOfWork->getOriginalEntityData($translation)->willReturn($originals);
        }

        $manager->getUnitOfWork()->willReturn($unitOfWork->reveal());

        return $manager;
    }

    /**
     * @param ObjectProphecy<EntityManagerInterface> $manager
     *
     * @return ObjectProphecy<ManagerRegistry>
     */
    private function managerRegistryReturning(ObjectProphecy $manager): ObjectProphecy
    {
        $registry = $this->prophesize(ManagerRegistry::class);
        $registry->getManagerForClass(Argument::any())->willReturn($manager->reveal());

        return $registry;
    }
}
