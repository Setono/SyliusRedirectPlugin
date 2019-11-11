<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
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
                                ManagerRegistry $managerRegistry,
                                RemovableRedirectFinderInterface $removableRedirectFinder,
                                array $validationGroups,
                                string $class
    ) {
        parent::__construct($requestStack, $validator, $managerRegistry, $removableRedirectFinder, $validationGroups, $class);

        $this->redirectFactory = $redirectFactory;
    }

    public function preUpdateProduct(ResourceControllerEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof ProductInterface) {
            return;
        }

        $uow = $this->getManager()->getUnitOfWork();
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
