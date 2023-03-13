<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

trait RedirectResponseTrait
{
    public static function getRedirectResponse(RedirectInterface $lastRedirect, ?string $queryString = null): RedirectResponse
    {
        $destination = $lastRedirect->getDestination();
        if ($lastRedirect->isKeepQueryString() && null !== $queryString) {
            $prefix = '?';
            if (false !== strpos($lastRedirect->getDestination(), '?')) {
                $prefix = '&';
            }
            $destination .= $prefix . $queryString;
        }

        return new RedirectResponse(
            $destination,
            $lastRedirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND
        );
    }
}
