<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Setono\SyliusRedirectPlugin\Decider\AutomaticRedirectCreationDeciderInterface;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

final class ProductUpdateListener extends AbstractUpdateListener
{
    /** @var RouterInterface */
    private $router;

    public function __construct(
        ValidatorInterface $validator,
        ManagerRegistry $managerRegistry,
        RemovableRedirectFinderInterface $removableRedirectFinder,
        RedirectFactoryInterface $redirectFactory,
        AutomaticRedirectCreationDeciderInterface $automaticRedirectCreationDecider,
        array $validationGroups,
        string $class,
        RouterInterface $router
    ) {
        parent::__construct($validator, $managerRegistry, $removableRedirectFinder, $redirectFactory, $automaticRedirectCreationDecider, $validationGroups, $class);

        $this->router = $router;
    }

    public function preUpdateProduct(ResourceControllerEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof ProductInterface) {
            return;
        }

        $productTranslations = $subject->getTranslations();
        /** @var ProductTranslationInterface $productTranslation */
        foreach ($productTranslations as $productTranslation) {
            $this->handleAutomaticRedirectCreation($productTranslation, $event);
        }
    }

    protected function getPostName(): string
    {
        return 'sylius_product';
    }

    protected function createRedirect(
        SlugAwareInterface $slugAware,
        string $source,
        string $destination,
        bool $permanent = true,
        bool $only404 = true
    ): RedirectInterface {
        /** @var ProductTranslationInterface $slugAware */
        Assert::isInstanceOf($slugAware, ProductTranslationInterface::class);

        /** @var ProductInterface $product */
        $product = $slugAware->getTranslatable();

        $sourceUrl = $this->router->generate('sylius_shop_product_show', ['slug' => $source]);
        $destinationUrl = $this->router->generate('sylius_shop_product_show', ['slug' => $destination]);

        $redirect = $this->redirectFactory->createNewWithValues(
            $sourceUrl,
            $destinationUrl,
            $permanent,
            $only404,
            $product->getChannels()
        );

        return $redirect;
    }
}
