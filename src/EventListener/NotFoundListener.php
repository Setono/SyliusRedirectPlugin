<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
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

    /** @var ChannelContextInterface */
    private $channelContext;

    /**
     * The $channelContext is default null because of BC. In v2.0 it will not be null by default
     */
    public function __construct(RedirectRepositoryInterface $redirectRepository, ObjectManager $objectManager, ChannelContextInterface $channelContext = null)
    {
        $this->redirectRepository = $redirectRepository;
        $this->objectManager = $objectManager;
        $this->channelContext = $channelContext;
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

        // BC
        if (null === $this->channelContext) {
            $redirect = $this->redirectRepository->findEnabledBySource($event->getRequest()->getPathInfo(), true);
        } else {
            $redirect = $this->redirectRepository->findEnabledBySourceAndChannel($event->getRequest()->getPathInfo(), $this->channelContext->getChannel(), true);
        }

        if (null === $redirect) {
            return;
        }

        $redirect->onAccess();
        $this->objectManager->flush();

        // BC
        if (null === $this->channelContext) {
            $lastRedirect = $this->redirectRepository->findLastRedirect($redirect, true);
        } else {
            $lastRedirect = $this->redirectRepository->findLastRedirectByChannel($redirect, $this->channelContext->getChannel(), true);
        }

        $event->setResponse(new RedirectResponse(
            $lastRedirect->getDestination(),
            $lastRedirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND
        ));
    }
}
