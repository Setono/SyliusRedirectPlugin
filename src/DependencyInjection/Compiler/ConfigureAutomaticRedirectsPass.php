<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\DependencyInjection\Compiler;

use Setono\SyliusRedirectPlugin\EventListener\AutomaticRedirectListener;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ConfigureAutomaticRedirectsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('setono_sylius_redirect.automatic_redirects')) {
            return;
        }

        /** @var array<string, bool> $automaticRedirects */
        $automaticRedirects = (array) $container->getParameter('setono_sylius_redirect.automatic_redirects');
        if ([] === $automaticRedirects) {
            return;
        }

        /** @var array<string, array{classes: array{model: string}}> $resources */
        $resources = $container->hasParameter('sylius.resources')
            ? (array) $container->getParameter('sylius.resources')
            : [];

        foreach (array_keys($automaticRedirects) as $alias) {
            if (!isset($resources[$alias])) {
                throw new InvalidConfigurationException(sprintf(
                    'The setono_sylius_redirect.automatic_redirects key "%s" is not a known Sylius resource alias.',
                    $alias,
                ));
            }

            $modelClass = $resources[$alias]['classes']['model'] ?? null;
            if (!is_string($modelClass) || !is_a($modelClass, SlugAwareInterface::class, true)) {
                throw new InvalidConfigurationException(sprintf(
                    'The setono_sylius_redirect.automatic_redirects key "%s" maps to model "%s", which does not implement %s.',
                    $alias,
                    (string) $modelClass,
                    SlugAwareInterface::class,
                ));
            }
        }

        if (!$container->hasDefinition(AutomaticRedirectListener::class)) {
            return;
        }

        $listener = $container->getDefinition(AutomaticRedirectListener::class);
        foreach (array_keys($automaticRedirects) as $alias) {
            $listener->addTag('kernel.event_listener', [
                'event' => $alias . '.pre_update',
                'method' => 'onPreUpdate',
            ]);
        }
    }
}
