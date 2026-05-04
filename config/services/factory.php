<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\Factory\RedirectFactory;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(RedirectFactory::class)
        ->decorate('setono_sylius_redirect.factory.redirect')
        ->args([service(RedirectFactory::class . '.inner')]);

    $services->alias(RedirectFactoryInterface::class, 'setono_sylius_redirect.factory.redirect');
};
