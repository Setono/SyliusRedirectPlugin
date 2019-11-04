<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

class NotFoundSubscriber implements EventSubscriberInterface
{
    /** @var ObjectManager */
    private $objectManager;

    /** @var ChannelContextInterface */
    private $channelContext;

    /** @var RedirectionPathResolverInterface */
    private $redirectionPathResolver;

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

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }
        $exception = $event->getException();

        if (!$exception instanceof HttpException || Response::HTTP_NOT_FOUND !== $exception->getStatusCode()) {
            return;
        }

        $redirectionPath = $this->redirectionPathResolver->resolveFromRequest(
            $event->getRequest(), $this->channelContext->getChannel(), true
        );

        if ($redirectionPath->isEmpty()) {
            return;
        }

        $redirectionPath->markAsAccessed();
        $this->objectManager->flush();

        $lastRedirect = $redirectionPath->last();
        Assert::notNull($lastRedirect);

        $event->setResponse(new RedirectResponse(
            $lastRedirect->getDestination(),
            $lastRedirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND
        ));
    }
}
