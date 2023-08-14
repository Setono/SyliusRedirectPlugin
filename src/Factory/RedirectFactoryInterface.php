<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Factory;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

interface RedirectFactoryInterface extends FactoryInterface
{
    public function createNew(): RedirectInterface;

    /**
     * @param iterable<array-key, ChannelInterface> $channels
     */
    public function createNewWithValues(
        string $source,
        string $destination,
        bool $permanent = true,
        bool $only404 = true,
        iterable $channels = []
    ): RedirectInterface;
}
