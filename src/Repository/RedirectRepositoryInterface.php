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
     * If more than one redirect matches the source this method will return the redirect that also matches the channel
     */
    public function findEnabledBySourceAndChannel(string $source, ChannelInterface $channel, bool $only404 = false): ?RedirectInterface;

    public function findEnabledBySource(string $source, bool $only404 = false, bool $fetchJoinChannels = false): ?RedirectInterface;

    public function searchNextRedirectByChannel(RedirectInterface $redirect, ChannelInterface $channel, bool $only404 = false): ?RedirectInterface;

    public function searchNextRedirect(RedirectInterface $redirection, bool $only404 = false): ?RedirectInterface;

    public function findLastRedirectByChannel(RedirectInterface $redirect, ChannelInterface $channel, bool $only404 = false): RedirectInterface;
}
