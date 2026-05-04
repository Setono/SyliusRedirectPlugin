<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinder;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolver;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(RemovableRedirectFinder::class)
        ->args([service(RedirectionPathResolver::class)]);

    $services->alias(RemovableRedirectFinderInterface::class, RemovableRedirectFinder::class);
};
