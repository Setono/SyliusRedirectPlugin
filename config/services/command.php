<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\Command\PruneCommand;
use Setono\SyliusRedirectPlugin\Pruner\Pruner;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(PruneCommand::class)
        ->args([
            service(Pruner::class),
            '%setono_sylius_redirect.remove_after%',
        ])
        ->tag('console.command');
};
