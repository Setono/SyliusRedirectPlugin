<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Repository;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface RedirectRepositoryInterface extends RepositoryInterface
{
    public function removeNotAccessed(int $threshold): void;

    /**
     * If the $channel is set and the underlying query returns two results, the result with a matching channel will be returned
     * If the $only404 is set, the underlying query will filter the redirects based on the value of this variable
     */
    public function findOneEnabledBySource(string $source, ChannelInterface $channel = null, bool $only404 = null): ?RedirectInterface;
}
