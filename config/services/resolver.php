<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolver;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('setono_sylius_redirect.resolver.redirection_path', RedirectionPathResolver::class)
        ->args([service('setono_sylius_redirect.repository.redirect')]);
};
