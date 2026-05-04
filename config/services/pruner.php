<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\Pruner\Pruner;
use Setono\SyliusRedirectPlugin\Pruner\PrunerInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(Pruner::class)
        ->args([
            service('doctrine'),
            '%setono_sylius_redirect.model.redirect.class%',
        ]);

    $services->alias(PrunerInterface::class, Pruner::class);
};
