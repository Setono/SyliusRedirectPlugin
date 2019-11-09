<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductTranslationUpdateListener
{
    /** @var \Symfony\Component\HttpFoundation\Request|null */
    private $request;

    /** @var RedirectFactoryInterface */
    private $redirectFactory;

    /** @var ValidatorInterface */
    private $validator;

    /** @var RemovableRedirectFinderInterface */
    private $removableRedirectFinder;

    /** @var array */
    private $validationGroups;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    public function __construct(RequestStack $requestStack,
                                RedirectFactoryInterface $redirectFactory,
                                ValidatorInterface $validator,
                                ManagerRegistry $managerRegistry,
                                RemovableRedirectFinderInterface $removableRedirectFinder,
                                array $validationGroups
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->redirectFactory = $redirectFactory;
        $this->validator = $validator;
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $managerRegistry->getManager();
        $this->entityManager = $entityManager;
        $this->removableRedirectFinder = $removableRedirectFinder;
        $this->validationGroups = $validationGroups;
    }

    public function preUpdateProduct(ResourceControllerEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof ProductInterface) {
            return;
        }

        $uow = $this->entityManager->getUnitOfWork();
        $productTranslations = $subject->getTranslations();
        /** @var ProductTranslationInterface $productTranslation */
        foreach ($productTranslations as $productTranslation) {
            $previous = $uow->getOriginalEntityData($productTranslation);
            $this->handleAutomaticRedirectCreation($productTranslation, $previous, $event);
        }
    }

    public function preUpdateProductTranslation(ResourceControllerEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof ProductTranslationInterface) {
            return;
        }
        $uow = $this->entityManager->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($subject);
        $this->handleAutomaticRedirectCreation($subject, $changeSet, $event);
    }

    private function handleAutomaticRedirectCreation(ProductTranslationInterface $productTranslation,
                                                     array $previous,
                                                     ResourceControllerEvent $event
    ): void {
        if (!isset($previous['slug'])) {
            return;
        }

        if (null === $this->request) {
            return;
        }

        /** @var array $postProductParams */
        $postProductParams = $this->request->request->get('sylius_product', []);
        if (0 === count($postProductParams)) {
            return;
        }
        $localeCode = $productTranslation->getLocale();

        if (!isset($postProductParams['translations']) || 0 === count($postProductParams['translations'][$localeCode])) {
            return;
        }

        $postProductTranslation = $postProductParams['translations'][$localeCode];
        if (!isset($postProductTranslation['addAutomaticRedirect']) || false === $postProductTranslation['addAutomaticRedirect']) {
            return;
        }

        $oldSlug = $previous['slug'];
        $newSlug = $productTranslation->getSlug();

        /** @var Product $product */
        $product = $productTranslation->getTranslatable();
        $redirect = $this->redirectFactory->createNewForProduct($product, $oldSlug, $newSlug, true, false);

        $removableRedirects = $this->removableRedirectFinder->findNextRedirect($redirect);
        foreach ($removableRedirects as $removableRedirect) {
            $this->entityManager->remove($removableRedirect);
        }

        $violations = $this->validator->validate($redirect, null, $this->validationGroups);
        if ($violations->count() > 0) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                $event->stop(
                    $violation->getMessageTemplate(),
                    ResourceControllerEvent::TYPE_ERROR,
                    $violation->getParameters()
                );

                return;
            }
        }

        $this->entityManager->persist($redirect);
    }
}
