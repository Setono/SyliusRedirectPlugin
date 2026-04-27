<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventSubscriber;

use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

final class ControllerSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    use RedirectResponseTrait;

    private LoggerInterface $logger;

    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly ChannelContextInterface $channelContext,
        private readonly RedirectionPathResolverInterface $redirectionPathResolver,
    ) {
        $this->logger = new NullLogger();
    }

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

        $request = $event->getRequest();
        $channel = null;

        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
        }
        $redirectionPath = $this->redirectionPathResolver->resolveFromRequest(
            $request,
            $channel,
        );

        if ($redirectionPath->isEmpty()) {
            return;
        }

        $redirectionPath->markAsAccessed();

        $this->objectManager->flush();

        $lastRedirect = $redirectionPath->last();
        Assert::notNull($lastRedirect);

        if ($lastRedirect->getDestination() === $request->getPathInfo()) {
            $this->logger->error('Infinite loop detected', [
                'url' => $request->getUri(),
                'redirectId' => $lastRedirect->getId(),
            ]);

            return;
        }

        $event->setResponse(self::getRedirectResponse($lastRedirect, $request->getQueryString()));
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
