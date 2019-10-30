<?php

declare(strict_types = 1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Resolver\InfiniteLoopResolverInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Exception\UpdateHandlingException;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductTranslationUpdateListener implements EventSubscriberInterface
{
    /** @var \Symfony\Component\HttpFoundation\Request|null */
    private $request;
    /** @var FactoryInterface */
    private $redirectionFactory;
    /** @var RepositoryInterface */
    private $redirectionRepository;
    /** @var InfiniteLoopResolverInterface */
    private $infiniteLoopResolver;
    /** @var RouterInterface */
    private $router;
    /** @var ValidatorInterface */
    private $validator;
    /** @var FlashBagInterface */
    private $flashBag;
    /** @var array */
    private $validationGroups;

    public function __construct(RequestStack $requestStack,
                                FactoryInterface $redirectionFactory,
                                RepositoryInterface $redirectionRepository,
                                InfiniteLoopResolverInterface $infiniteLoopResolver,
                                RouterInterface $router,
                                ValidatorInterface $validator,
                                FlashBagInterface $flashBag,
                                array $validationGroups
    )
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->redirectionFactory = $redirectionFactory;
        $this->redirectionRepository = $redirectionRepository;
        $this->infiniteLoopResolver = $infiniteLoopResolver;
        $this->router = $router;
        $this->validator = $validator;
        $this->flashBag = $flashBag;
        $this->validationGroups = $validationGroups;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate
        ];
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $productTranslation = $event->getObject();
        dump($event);
        die();
        if (!$productTranslation instanceof ProductTranslationInterface) {
            return;
        }

        $changeSet = $event->getEntityChangeSet();
        $this->handleAutomaticRedirectionCreation($productTranslation, $changeSet);
    }

    private function handleAutomaticRedirectionCreation(ProductTranslationInterface $productTranslation, $changeSet): void
    {
        if (empty($changeSet['slug'])) {
            return;
        }

        if (null === $this->request) {
            return;
        }

        $postProductParams = $this->request->request->get('sylius_product', []);
        if (empty($postProductParams)) {
            return;
        }
        $localeCode = $productTranslation->getLocale();

        if (empty($postProductParams['translations'][$localeCode])) {
            return;
        }

        $postProductTranslation = $postProductParams['translations'][$localeCode];
        if (empty($postProductTranslation['addAutomaticRedirection'])) {
            return;
        }

        $oldSlug = $changeSet['slug'][0];
        $newSlug = $changeSet['slug'][1];

        /** @var RedirectInterface $redirect */
        $redirect = $this->redirectionFactory->createNew();
        $source = $this->router->generate('sylius_shop_product_show', ['slug' => $oldSlug]);
        $destination = $this->router->generate('sylius_shop_product_show', ['slug' => $newSlug]);

        $redirect->setSource($source);
        $redirect->setDestination($destination);
        $redirect->enable();
        $redirect->setOnly404(false);
        $redirect->setPermanent(true);

        $conflictingRedirect = $this->infiniteLoopResolver->getConflictingRedirect($redirect);
        if ($conflictingRedirect instanceof RedirectInterface) {
            $this->redirectionRepository->remove($conflictingRedirect);
        }

        $violations = $this->validator->validate($redirect, null, $this->validationGroups);
        if ($violations->count() > 0) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                $this->flashBag->add('error', [
                    'message' => $violation->getMessageTemplate(),
                    'parameters' => $violation->getParameters()
                ]);
            }
            throw new UpdateHandlingException();
        }

        $this->redirectionRepository->add($redirect);
    }
}
