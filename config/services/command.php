<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\Command\PruneCommand;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('setono_sylius_redirect.command.prune', PruneCommand::class)
        ->args([
            service('setono_sylius_redirect.pruner.redirect'),
            '%setono_sylius_redirect.remove_after%',
        ])
        ->tag('console.command');
};
