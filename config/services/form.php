<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\Form\Type\RedirectType;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('setono_sylius_redirect.form.type.redirect.validation_groups', ['setono_sylius_redirect']);

    $services = $container->services();

    $services->set(RedirectType::class)
        ->args([
            '%setono_sylius_redirect.model.redirect.class%',
            '%setono_sylius_redirect.form.type.redirect.validation_groups%',
            '%setono_sylius_redirect.allow_non_404_redirects%',
        ])
        ->tag('form.type');
};
