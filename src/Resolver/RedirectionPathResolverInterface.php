<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Resolver;

use Setono\SyliusRedirectPlugin\Exception\InfiniteLoopException;
use Setono\SyliusRedirectPlugin\Model\RedirectionPath;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\HttpFoundation\Request;

interface RedirectionPathResolverInterface
{
    /**
     * @throws InfiniteLoopException if the redirect path has a cycle, i.e. infinite loop
     */
    public function resolve(string $source, ChannelInterface $channel = null, bool $only404 = false): RedirectionPath;

    /**
     * @throws InfiniteLoopException if the redirect path has a cycle, i.e. infinite loop
     */
    public function resolveFromRequest(
        Request $request,
        ChannelInterface $channel = null,
        bool $only404 = false
    ): RedirectionPath;
}
