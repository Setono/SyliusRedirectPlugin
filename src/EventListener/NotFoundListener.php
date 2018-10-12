<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class NotFoundListener
{
    /**
     * @var RedirectRepository
     */
    private $redirectRepository;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(RedirectRepository $redirectRepository, ObjectManager $objectManager)
    {
        $this->redirectRepository = $redirectRepository;
        $this->objectManager = $objectManager;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }
        $exception = $event->getException();

        if (!$exception instanceof HttpException || 404 !== (int) $exception->getStatusCode()) {
            return;
        }

        $redirect = $this->redirectRepository->findBySource($event->getRequest()->getPathInfo());

        if (null === $redirect) {
            return;
        }

        $redirect->onAccess();
        $this->objectManager->flush();

        $event->setResponse(new RedirectResponse($redirect->getDestination(), $redirect->isPermanent() ? 301 : 302));
    }
}
