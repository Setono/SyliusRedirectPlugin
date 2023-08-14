<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventSubscriber;

use Doctrine\Persistence\ObjectManager;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

class NotFoundSubscriber implements EventSubscriberInterface
{
    use RedirectResponseTrait;

    private ObjectManager $objectManager;

    private ChannelContextInterface $channelContext;

    private RedirectionPathResolverInterface $redirectionPathResolver;

    public function __construct(
        ObjectManager $objectManager,
        ChannelContextInterface $channelContext,
        RedirectionPathResolverInterface $redirectionPathResolver
    ) {
        $this->objectManager = $objectManager;
        $this->channelContext = $channelContext;
        $this->redirectionPathResolver = $redirectionPathResolver;
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
        } catch (ChannelNotFoundException $e) {
        }

        $request = $event->getRequest();
        $redirectionPath = $this->redirectionPathResolver->resolveFromRequest(
            $request,
            $channel,
            true
        );

        if ($redirectionPath->isEmpty()) {
            return;
        }

        $redirectionPath->markAsAccessed();
        $this->objectManager->flush();

        $lastRedirect = $redirectionPath->last();
        Assert::notNull($lastRedirect);

        $event->setResponse(self::getRedirectResponse($lastRedirect, $request->getQueryString()));
    }
}
