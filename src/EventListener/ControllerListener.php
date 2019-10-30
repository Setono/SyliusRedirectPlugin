<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ControllerListener implements EventSubscriberInterface
{
    /** @var RedirectRepositoryInterface */
    private $redirectRepository;

    /** @var ObjectManager */
    private $objectManager;

    /** @var ChannelContextInterface|null */
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
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // BC
        if (null === $this->channelContext) {
            $redirect = $this->redirectRepository->findEnabledBySource($pathInfo);
        } else {
            $redirect = $this->redirectRepository->findEnabledBySourceAndChannel($pathInfo, $this->channelContext->getChannel());
        }

        if (null === $redirect) {
            return;
        }

        $redirect->onAccess();
        $this->objectManager->flush();

        // BC
        if (null === $this->channelContext) {
            $lastRedirect = $this->redirectRepository->findLastRedirect($redirect);
        } else {
            $lastRedirect = $this->redirectRepository->findLastRedirectByChannel($redirect, $this->channelContext->getChannel());
        }

        $event->setController(static function () use ($lastRedirect): RedirectResponse {
            return new RedirectResponse(
                $lastRedirect->getDestination(),
                $lastRedirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND
            );
        });
    }
}
