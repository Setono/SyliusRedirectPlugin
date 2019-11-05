<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Resolver\InfiniteLoopResolverInterface;
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
                                RedirectFactoryInterface $redirectionFactory,
                                RepositoryInterface $redirectionRepository,
                                InfiniteLoopResolverInterface $infiniteLoopResolver,
                                RouterInterface $router,
                                ValidatorInterface $validator,
                                FlashBagInterface $flashBag,
                                array $validationGroups
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->redirectionFactory = $redirectionFactory;
        $this->redirectionRepository = $redirectionRepository;
        $this->infiniteLoopResolver = $infiniteLoopResolver;
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

        if (0 === count($postProductParams['translations'][$localeCode])) {
            return;
        }

        $postProductTranslation = $postProductParams['translations'][$localeCode];
        if (!isset($postProductTranslation['addAutomaticRedirection']) || 0 === count($postProductTranslation['addAutomaticRedirection'])) {
            return;
        }

        $oldSlug = $changeSet['slug'][0];
        $newSlug = $changeSet['slug'][1];

        $source = $this->router->generate('sylius_shop_product_show', ['slug' => $oldSlug]);
        $destination = $this->router->generate('sylius_shop_product_show', ['slug' => $newSlug]);

        $redirect = $this->redirectionFactory->createNewWithValues($source, $destination, true, false);

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
                    'parameters' => $violation->getParameters(),
                ]);
            }

            throw new UpdateHandlingException();
        }

        $this->redirectionRepository->add($redirect);
    }
}
