<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\Controller\Admin\CheckSourceAction;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(CheckSourceAction::class)
        ->public()
        ->args([
            service('setono_sylius_redirect.repository.redirect'),
            service('router'),
        ]);
};
