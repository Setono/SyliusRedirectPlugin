<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Webmozart\Assert\Assert;

final class ProductUpdateListener extends AbstractTranslationUpdateListener
{
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
        /** @var \Sylius\Component\Core\Model\ProductInterface $product */
        $product = $slugAware->getTranslatable();
        $redirect = $this->redirectFactory->createNewForProduct($product, $source, $destination, $permanent, $only404);

        return $redirect;
    }
}
