<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Finder;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;

interface RemovableRedirectFinderInterface
{
    /**
     * Returns the redirects whose source matches the destination of the given redirect.
     *
     * If the redirect has multiple channels the lookup is performed for each channel.
     * Ie: With a RedirectionPath ['a -> b', 'b -> c'] (no channels), findRedirectsTargetedBy('c -> a')
     * yields ['a -> b']. If those redirects had multiple channels, the result would be
     * ['a -> b (channel1)', 'a -> b (channel2)', ...].
     *
     * @return iterable<RedirectInterface>
     */
    public function findRedirectsTargetedBy(RedirectInterface $redirect): iterable;
}
