<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\Twig\Extension;
use Setono\SyliusRedirectPlugin\Twig\Runtime;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('setono_sylius_redirect.twig.extension', Extension::class)
        ->tag('twig.extension');

    $services->set('setono_sylius_redirect.twig.runtime', Runtime::class)
        ->args([service('sylius.repository.channel')])
        ->tag('twig.runtime');
};
