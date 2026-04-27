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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

class NotFoundSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    use RedirectResponseTrait;

    private LoggerInterface $logger;

    public function __construct(
        private ObjectManager $objectManager,
        private ChannelContextInterface $channelContext,
        private RedirectionPathResolverInterface $redirectionPathResolver,
    ) {
        $this->logger = new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $throwable = $event->getThrowable();
        if (!$throwable instanceof HttpException || $throwable->getStatusCode() !== Response::HTTP_NOT_FOUND) {
            return;
        }
        $channel = null;

        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
        }

        $request = $event->getRequest();
        $redirectionPath = $this->redirectionPathResolver->resolveFromRequest(
            $request,
            $channel,
            true,
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
