<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventSubscriber;

use Doctrine\Persistence\ObjectManager;
use League\Uri\Uri;
use League\Uri\UriModifier;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractRedirectSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly ChannelContextInterface $channelContext,
        private readonly RedirectionPathResolverInterface $redirectionPathResolver,
    ) {
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function resolveRedirectResponse(Request $request, bool $only404): ?RedirectResponse
    {
        $channel = null;

        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
        }

        $redirectionPath = $this->redirectionPathResolver->resolveFromRequest($request, $channel, $only404);
        if ($redirectionPath->isEmpty()) {
            return null;
        }

        $lastRedirect = $redirectionPath->last();

        $redirectionPath->markAsAccessed();
        $this->objectManager->flush();

        if ($lastRedirect->getDestination() === $request->getPathInfo()) {
            $this->logger->error('Infinite loop detected', [
                'url' => $request->getUri(),
                'redirectId' => $lastRedirect->getId(),
            ]);

            return null;
        }

        return self::buildRedirectResponse($lastRedirect, $request->getQueryString());
    }

    private static function buildRedirectResponse(RedirectInterface $lastRedirect, ?string $queryString): RedirectResponse
    {
        $uri = Uri::createFromString((string) $lastRedirect->getDestination());

        if ($lastRedirect->keepQueryString() && null !== $queryString) {
            $uri = UriModifier::appendQuery($uri, $queryString);
        }

        return new RedirectResponse(
            $uri->__toString(),
            $lastRedirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND,
        );
    }
}
