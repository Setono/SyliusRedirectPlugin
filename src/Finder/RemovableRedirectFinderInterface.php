<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Finder;

use Doctrine\Common\Collections\Collection;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;

interface RemovableRedirectFinderInterface
{
    /**
     * Returns the redirects that have for source the destination of the given redirect
     *
     * If the redirect has multiple channels it does the operation for each channel
     * Ie: There is a RedirectionPath with ['a -> b', 'b -> c'], both without channel, findRedirectsTargetedBy->('c -> a');
     * will return new ArrayCollection(['a -> b']);
     * If all those redirect had multiple channels, this would result in :
     *      new ArrayCollection(['a -> b (channel1)', 'a -> b (channel2)', 'a -> b (channel3)', ...])
     *
     * @return Collection|RedirectInterface[]
     */
    public function findRedirectsTargetedBy(RedirectInterface $redirect): Collection;
}
