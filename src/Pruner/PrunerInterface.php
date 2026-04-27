<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Pruner;

interface PrunerInterface
{
    /**
     * Removes redirects that have not been accessed in the last $thresholdInDays days.
     *
     * Returns the number of redirects removed.
     */
    public function prune(int $thresholdInDays): int;
}
