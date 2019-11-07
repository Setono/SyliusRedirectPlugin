<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Factory;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class RedirectFactory implements RedirectFactoryInterface
{
    /** @var FactoryInterface */
    private $decoratedFactory;

    public function __construct(FactoryInterface $decoratedFactory)
    {
        $this->decoratedFactory = $decoratedFactory;
    }

    public function createNewWithValues(string $source,
                                        string $destination,
                                        bool $permanent = true,
                                        bool $only404 = true
    ): RedirectInterface {
        $redirect = $this->createNew();

        $redirect->setSource($source);
        $redirect->setDestination($destination);
        $redirect->setOnly404($only404);
        $redirect->setPermanent($permanent);
        $redirect->enable();

        return $redirect;
    }

    public function createNew(): RedirectInterface
    {
        /** @var RedirectInterface $redirect */
        $redirect = $this->decoratedFactory->createNew();

        return $redirect;
    }
}
