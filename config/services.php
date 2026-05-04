<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->import('services/command.php');
    $container->import('services/constraint.php');
    $container->import('services/controller.php');
    $container->import('services/event_subscriber.php');
    $container->import('services/factory.php');
    $container->import('services/finder.php');
    $container->import('services/form.php');
    $container->import('services/pruner.php');
    $container->import('services/resolver.php');
    $container->import('services/twig.php');
    $container->import('services/url_resolver.php');
};
