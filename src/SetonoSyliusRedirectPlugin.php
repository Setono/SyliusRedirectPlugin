<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;

final class SetonoSyliusRedirectPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    /**
     * {@inheritdoc}
     */
    public function getSupportedDrivers(): array
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getModelNamespace(): string
    {
        return 'Setono\SyliusRedirectPlugin\Model';
    }
}
