<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\EventListener\AutomaticRedirectListener;
use Setono\SyliusRedirectPlugin\EventSubscriber\AdminMenuSubscriber;
use Setono\SyliusRedirectPlugin\EventSubscriber\NotFoundSubscriber;
use Setono\SyliusRedirectPlugin\EventSubscriber\RequestSubscriber;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinder;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolver;
use Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolver;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(AdminMenuSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(RequestSubscriber::class)
        ->args([
            service('doctrine'),
            service('sylius.context.channel'),
            service(RedirectionPathResolver::class),
        ])
        ->call('setLogger', [service('logger')->ignoreOnInvalid()])
        ->tag('kernel.event_subscriber');

    $services->set(NotFoundSubscriber::class)
        ->args([
            service('doctrine'),
            service('sylius.context.channel'),
            service(RedirectionPathResolver::class),
        ])
        ->call('setLogger', [service('logger')->ignoreOnInvalid()])
        ->tag('kernel.event_subscriber');

    // kernel.event_listener tags are added at compile time by
    // Setono\SyliusRedirectPlugin\DependencyInjection\Compiler\ConfigureAutomaticRedirectsPass,
    // one per alias enabled in setono_sylius_redirect.automatic_redirects.
    $services->set(AutomaticRedirectListener::class)
        ->args([
            service('doctrine'),
            service('setono_sylius_redirect.factory.redirect'),
            service(AutomaticRedirectUrlResolver::class),
            service(RemovableRedirectFinder::class),
            service('validator'),
            '%setono_sylius_redirect.form.type.redirect.validation_groups%',
        ])
        ->call('setLogger', [service('logger')->ignoreOnInvalid()]);
};
