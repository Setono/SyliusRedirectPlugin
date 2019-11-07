<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Exception\UpdateHandlingException;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductTranslationUpdateListener
{
    /** @var \Symfony\Component\HttpFoundation\Request|null */
    private $request;

    /** @var RedirectFactoryInterface */
    private $redirectionFactory;

    /** @var RepositoryInterface */
    private $redirectionRepository;

    /** @var RedirectionPathResolverInterface */
    private $redirectionPathResolver;

    /** @var RouterInterface */
    private $router;

    /** @var ValidatorInterface */
    private $validator;

    /** @var FlashBagInterface */
    private $flashBag;

    /** @var array */
    private $validationGroups;

    public function __construct(RequestStack $requestStack,
                                RedirectFactoryInterface $redirectionFactory,
                                RepositoryInterface $redirectionRepository,
                                RedirectionPathResolverInterface $redirectionPathResolver,
                                RouterInterface $router,
                                ValidatorInterface $validator,
                                FlashBagInterface $flashBag,
                                array $validationGroups
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->redirectionFactory = $redirectionFactory;
        $this->redirectionRepository = $redirectionRepository;
        $this->redirectionPathResolver = $redirectionPathResolver;
        $this->router = $router;
        $this->validator = $validator;
        $this->flashBag = $flashBag;
        $this->validationGroups = $validationGroups;
    }

    public function postUpdate(LifecycleEventArgs $event): void
    {
        $productTranslation = $event->getObject();
        if (!$productTranslation instanceof ProductTranslationInterface) {
            return;
        }

        $uow = $event->getEntityManager()->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($productTranslation);
        $this->handleAutomaticRedirectionCreation($productTranslation, $changeSet);
    }

    /**
     * @param ProductTranslationInterface $productTranslation
     * @param array                       $changeSet
     *
     * @throws UpdateHandlingException
     */
    private function handleAutomaticRedirectionCreation(ProductTranslationInterface $productTranslation, array $changeSet): void
    {
        if (!isset($changeSet['slug']) || 0 === count($changeSet['slug'])) {
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
        if (!isset($postProductTranslation['addAutomaticRedirection']) || false === $postProductTranslation['addAutomaticRedirection']) {
            return;
        }

        $oldSlug = $changeSet['slug'][0];
        $newSlug = $changeSet['slug'][1];

        $source = $this->router->generate('sylius_shop_product_show', ['slug' => $oldSlug]);
        $destination = $this->router->generate('sylius_shop_product_show', ['slug' => $newSlug]);

        /** @var Product $product */
        $product = $productTranslation->getTranslatable();
        $channels = $product->getChannels();
        $redirect = $this->redirectionFactory->createNewWithValues($source, $destination, true, false);

        foreach ($channels as $channel) {
            $redirect->addChannel($channel);
        }

        if ($redirect->getChannels()->isEmpty()) {
            $redirectionPath = $this->redirectionPathResolver->resolve($redirect->getDestination());
            if (!$redirectionPath->isEmpty()) {
                $this->redirectionRepository->remove($redirectionPath->first());
            }
        } else {
            foreach ($redirect->getChannels() as $channel) {
                $redirectionPath = $this->redirectionPathResolver->resolve($redirect->getDestination(), $channel);
                if (!$redirectionPath->isEmpty()) {
                    $this->redirectionRepository->remove($redirectionPath->first());
                }
            }
        }

        $violations = $this->validator->validate($redirect, null, $this->validationGroups);
        if ($violations->count() > 0) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                $this->flashBag->add('error', [
                    'message' => $violation->getMessageTemplate(),
                    'parameters' => $violation->getParameters(),
                ]);
            }

            throw new UpdateHandlingException();
        }

        $this->redirectionRepository->add($redirect);
    }
}
