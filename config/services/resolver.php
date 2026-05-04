<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolver;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(RedirectionPathResolver::class)
        ->args([service('setono_sylius_redirect.repository.redirect')]);

    $services->alias(RedirectionPathResolverInterface::class, RedirectionPathResolver::class);
};
