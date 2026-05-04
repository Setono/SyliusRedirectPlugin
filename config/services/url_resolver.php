<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolver;
use Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolverInterface;
use Setono\SyliusRedirectPlugin\UrlResolver\ProductAutomaticRedirectUrlResolver;
use Setono\SyliusRedirectPlugin\UrlResolver\TaxonAutomaticRedirectUrlResolver;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(AutomaticRedirectUrlResolver::class);

    $services->alias(AutomaticRedirectUrlResolverInterface::class, AutomaticRedirectUrlResolver::class);

    $services->set(ProductAutomaticRedirectUrlResolver::class)
        ->args([service('router')])
        ->tag('setono_sylius_redirect.automatic_redirect_url_resolver');

    $services->set(TaxonAutomaticRedirectUrlResolver::class)
        ->args([service('router')])
        ->tag('setono_sylius_redirect.automatic_redirect_url_resolver');
};
