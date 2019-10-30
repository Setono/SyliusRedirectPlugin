<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class NotFoundListener implements EventSubscriberInterface
{
    /** @var RedirectRepositoryInterface */
    private $redirectRepository;

    /** @var ObjectManager */
    private $objectManager;

    public function __construct(RedirectRepositoryInterface $redirectRepository, ObjectManager $objectManager)
    {
        $this->redirectRepository = $redirectRepository;
        $this->objectManager = $objectManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }
        $exception = $event->getException();

        if (!$exception instanceof HttpException || Response::HTTP_NOT_FOUND !== $exception->getStatusCode()) {
            return;
        }

        $redirect = $this->redirectRepository->findEnabledBySource($event->getRequest()->getPathInfo(), true);

        if (null === $redirect) {
            return;
        }

        $redirect->onAccess();
        $this->objectManager->flush();

        $lastRedirect = $this->redirectRepository->findLastRedirect($redirect, true);

        $event->setResponse(new RedirectResponse(
            $lastRedirect->getDestination(),
            $lastRedirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND
        ));
    }
}
