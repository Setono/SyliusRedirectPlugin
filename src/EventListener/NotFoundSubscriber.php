<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Persistence\ObjectManager;
use Setono\MainRequestTrait\MainRequestTrait;
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
    use MainRequestTrait;

    use RedirectResponseTrait;

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

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$this->isMainRequest($event)) {
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
