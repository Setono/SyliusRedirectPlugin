<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductTranslationUpdateListener
{
    /** @var \Symfony\Component\HttpFoundation\Request|null */
    private $request;

    /** @var RedirectFactoryInterface */
    private $redirectFactory;

    /** @var RepositoryInterface */
    private $redirectRepository;

    /** @var RedirectionPathResolverInterface */
    private $redirectionPathResolver;

    /** @var ValidatorInterface */
    private $validator;

    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var array */
    private $validationGroups;

    public function __construct(RequestStack $requestStack,
                                RedirectFactoryInterface $redirectFactory,
                                RepositoryInterface $redirectRepository,
                                RedirectionPathResolverInterface $redirectionPathResolver,
                                ValidatorInterface $validator,
                                ManagerRegistry $managerRegistry,
                                array $validationGroups
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->redirectFactory = $redirectFactory;
        $this->redirectRepository = $redirectRepository;
        $this->redirectionPathResolver = $redirectionPathResolver;
        $this->validator = $validator;
        $this->managerRegistry = $managerRegistry;
        $this->validationGroups = $validationGroups;
    }

    public function preUpdateProduct(ResourceControllerEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof ProductInterface) {
            return;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->managerRegistry->getManager();
        $uow = $em->getUnitOfWork();
        $productTranslations = $subject->getTranslations();
        foreach ($productTranslations as $productTranslation) {
            $previous = $uow->getOriginalEntityData($productTranslation);
            $this->handleAutomaticRedirectCreation($productTranslation, $previous, $event);
        }
    }

    public function preUpdateProductTranslation(GenericEvent $event): void
    {
        /*$subject = $event->getSubject();
        if (!$subject instanceof ProductTranslationInterface) {
            return;
        }
        $uow = $this->objectManager->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($subject);
        $this->handleAutomaticRedirectCreation($subject, $changeSet);*/
    }

    private function handleAutomaticRedirectCreation(ProductTranslationInterface $productTranslation,
                                                     array $previous,
                                                     ResourceControllerEvent $event
    ): void
    {
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

        if ($redirect->getChannels()->isEmpty()) {
            $redirectionPath = $this->redirectionPathResolver->resolve($redirect->getDestination());
            if (!$redirectionPath->isEmpty()) {
                $this->redirectRepository->remove($redirectionPath->first());
            }
        } else {
            foreach ($redirect->getChannels() as $channel) {
                $redirectionPath = $this->redirectionPathResolver->resolve($redirect->getDestination(), $channel);
                if (!$redirectionPath->isEmpty()) {
                    $this->redirectRepository->remove($redirectionPath->first());
                }
            }
        }

        $violations = $this->validator->validate($redirect, null, $this->validationGroups);
        if ($violations->count() > 0) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                $event->setMessage($violation->getMessageTemplate());
                $event->setMessageParameters($violation->getParameters());
                $event->stopPropagation();

                return;
            }
        }

        $this->redirectRepository->add($redirect);
    }
}
