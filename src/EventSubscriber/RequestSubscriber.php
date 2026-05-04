<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventSubscriber;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestSubscriber extends AbstractRedirectSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            // priority 31: after RouterListener (32) but before NonChannelLocaleListener (10) and LocaleListener (16)
            KernelEvents::REQUEST => ['onKernelRequest', 31],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $this->resolveRedirectResponse($event->getRequest(), false);
        if (null === $response) {
            return;
        }

        $event->setResponse($response);
    }
}
