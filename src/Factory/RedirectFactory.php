<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Factory;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class RedirectFactory implements RedirectFactoryInterface
{
    private FactoryInterface $decoratedFactory;

    public function __construct(FactoryInterface $decoratedFactory)
    {
        $this->decoratedFactory = $decoratedFactory;
    }

    public function createNew(): RedirectInterface
    {
        /** @var object|RedirectInterface $redirect */
        $redirect = $this->decoratedFactory->createNew();
        Assert::isInstanceOf($redirect, RedirectInterface::class);

        return $redirect;
    }

    public function createNewWithValues(
        string $source,
        string $destination,
        bool $permanent = true,
        bool $only404 = true,
        iterable $channels = []
    ): RedirectInterface {
        $redirect = $this->createNew();

        $redirect->setSource($source);
        $redirect->setDestination($destination);
        $redirect->setOnly404($only404);
        $redirect->setPermanent($permanent);
        $redirect->enable();

        foreach ($channels as $channel) {
            $redirect->addChannel($channel);
        }

        return $redirect;
    }
}
