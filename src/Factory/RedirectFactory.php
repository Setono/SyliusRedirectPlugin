<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Factory;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Routing\RouterInterface;

final class RedirectFactory implements RedirectFactoryInterface
{
    /** @var FactoryInterface */
    private $decoratedFactory;

    /** @var RouterInterface */
    private $router;

    public function __construct(FactoryInterface $decoratedFactory, RouterInterface $router)
    {
        $this->decoratedFactory = $decoratedFactory;
        $this->router = $router;
    }

    public function createNew(): RedirectInterface
    {
        /** @var RedirectInterface $redirect */
        $redirect = $this->decoratedFactory->createNew();

        return $redirect;
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

    public function createNewForProduct(ProductInterface $product,
                                        string $source,
                                        string $destination,
                                        bool $permanent = true,
                                        bool $only404 = true
    ): RedirectInterface {
        $source = $this->router->generate('sylius_shop_product_show', ['slug' => $source]);
        $destination = $this->router->generate('sylius_shop_product_show', ['slug' => $destination]);

        $redirect = $this->createNewWithValues($source, $destination, $permanent, $only404);

        $channels = $product->getChannels();
        foreach ($channels as $channel) {
            $redirect->addChannel($channel);
        }

        return $redirect;
    }
}
