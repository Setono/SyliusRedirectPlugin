<?php

declare(strict_types = 1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductTranslationUpdateListener
{
    private $request;
    private $redirectionFactory;
    private $redirectionRepository;
    private $router;
    private $validator;

    public function __construct(RequestStack $requestStack,
                                FactoryInterface $redirectionFactory,
                                RepositoryInterface $redirectionRepository,
                                RouterInterface $router,
                                ValidatorInterface $validator
    )
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->redirectionFactory = $redirectionFactory;
        $this->redirectionRepository = $redirectionRepository;
        $this->router = $router;
        $this->validator = $validator;
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

    private function handleAutomaticRedirectionCreation(ProductTranslationInterface $productTranslation, $changeSet): void
    {
        if (empty($changeSet['slug'])) {
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

        /** @var RedirectInterface $redirection */
        $redirection = $this->redirectionFactory->createNew();
        $source = $this->router->generate('sylius_shop_product_show', ['slug' => $oldSlug]);
        $destination = $this->router->generate('sylius_shop_product_show', ['slug' => $newSlug]);

        $redirection->setSource($source);
        $redirection->setDestination($destination);
        $redirection->enable();
        $redirection->setOnly404(false);
        $redirection->setPermanent(true);

        $violations = $this->validator->validate($redirection, null, ['sylius']);
        if (!empty($violations)) {
            return;
        }

        $this->redirectionRepository->add($redirection);
    }
}
