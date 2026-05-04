<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\EventListener\AutomaticRedirectListener;
use Setono\SyliusRedirectPlugin\EventSubscriber\NotFoundSubscriber;
use Setono\SyliusRedirectPlugin\EventSubscriber\RequestSubscriber;
use Setono\SyliusRedirectPlugin\Menu\AdminMenuListener;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('setono_sylius_redirect_plugin.menu.admin_menu', AdminMenuListener::class)
        ->tag('kernel.event_listener', [
            'event' => 'sylius.menu.admin.main',
            'method' => 'addAdminMenuItems',
        ]);

    $services->set('setono_sylius_redirect.event_subscriber.request', RequestSubscriber::class)
        ->args([
            service('setono_sylius_redirect.manager.redirect'),
            service('sylius.context.channel'),
            service('setono_sylius_redirect.resolver.redirection_path'),
        ])
        ->call('setLogger', [service('logger')->ignoreOnInvalid()])
        ->tag('kernel.event_subscriber');

    $services->set('setono_sylius_redirect.event_listener.not_found', NotFoundSubscriber::class)
        ->args([
            service('setono_sylius_redirect.manager.redirect'),
            service('sylius.context.channel'),
            service('setono_sylius_redirect.resolver.redirection_path'),
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
            service('setono_sylius_redirect.url_resolver.automatic_redirect.composite'),
            service('setono_sylius_redirect.finder.removable_redirect'),
            service('validator'),
            '%setono_sylius_redirect.form.type.redirect.validation_groups%',
        ])
        ->call('setLogger', [service('logger')->ignoreOnInvalid()]);
};
