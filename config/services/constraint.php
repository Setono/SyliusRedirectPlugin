<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Setono\SyliusRedirectPlugin\Validator\Constraints\InfiniteLoopValidator;
use Setono\SyliusRedirectPlugin\Validator\Constraints\UniqueSourcePerChannelValidator;
use Setono\SyliusRedirectPlugin\Validator\Constraints\UniqueSourceValidator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(InfiniteLoopValidator::class)
        ->args([
            service('sylius.repository.channel'),
            service('setono_sylius_redirect.resolver.redirection_path'),
        ])
        ->tag('validator.constraint_validator');

    $services->set(UniqueSourceValidator::class)
        ->args([service('setono_sylius_redirect.repository.redirect')])
        ->tag('validator.constraint_validator');

    $services->set(UniqueSourcePerChannelValidator::class)
        ->args([service('setono_sylius_redirect.repository.redirect')])
        ->tag('validator.constraint_validator');
};
