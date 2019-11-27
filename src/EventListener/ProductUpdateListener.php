<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;

final class ProductUpdateListener extends AbstractUpdateListener
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
}
