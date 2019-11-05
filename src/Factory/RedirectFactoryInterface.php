<?php

declare(strict_types = 1);

namespace Setono\SyliusRedirectPlugin\Factory;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

interface RedirectFactoryInterface extends FactoryInterface
{
    public function createNew(): RedirectInterface;

    public function createNewWithValues(string $source,
                                        string $destination,
                                        bool $permanent = false,
                                        bool $only404 = false
    ): RedirectInterface;
}
