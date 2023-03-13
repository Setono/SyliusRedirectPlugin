<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use League\Uri\Uri;
use League\Uri\UriModifier;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

trait RedirectResponseTrait
{
    public static function getRedirectResponse(RedirectInterface $lastRedirect, ?string $queryString = null): RedirectResponse
    {
        $uri = Uri::createFromString($lastRedirect->getDestination());

        if ($lastRedirect->isKeepQueryString() && null !== $queryString) {
            $uri = UriModifier::appendQuery($uri, $queryString);
        }

        return new RedirectResponse(
            $uri->toString(),
            $lastRedirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND
        );
    }
}
