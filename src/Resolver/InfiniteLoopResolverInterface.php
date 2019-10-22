<?php

declare(strict_types = 1);

namespace Setono\SyliusRedirectPlugin\Resolver;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;

interface InfiniteLoopResolverInterface
{
    public function generatesInfiniteLoop(RedirectInterface $redirect): bool;

    public function getConflictingRedirect(RedirectInterface $redirect): ?RedirectInterface;
}
