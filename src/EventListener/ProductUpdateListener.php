<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

final class ProductUpdateListener extends AbstractTranslationUpdateListener
{
    /** @var RedirectFactoryInterface */
    private $redirectFactory;

    public function __construct(RedirectFactoryInterface $redirectFactory,
                                RequestStack $requestStack,
                                ValidatorInterface $validator,
                                EntityManagerInterface $objectManager,
                                RemovableRedirectFinderInterface $removableRedirectFinder,
                                array $validationGroups,
                                string $class
    ) {
        parent::__construct($requestStack, $validator, $objectManager, $removableRedirectFinder, $validationGroups, $class);

        $this->redirectFactory = $redirectFactory;
    }

    public function preUpdateProduct(ResourceControllerEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof ProductInterface) {
            return;
        }

        $uow = $this->objectManager->getUnitOfWork();
        $productTranslations = $subject->getTranslations();
        /** @var ProductTranslationInterface $productTranslation */
        foreach ($productTranslations as $productTranslation) {
            $previous = $uow->getOriginalEntityData($productTranslation);
            $this->handleAutomaticRedirectCreation($productTranslation, $previous, $event);
        }
    }

    protected function getPostName(): string
    {
        return 'sylius_product';
    }

    protected function createRedirect(SlugAwareInterface $slugAware,
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
