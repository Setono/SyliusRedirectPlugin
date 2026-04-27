<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusRedirectExtension extends AbstractResourceExtension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{driver: string, resources: array<string, mixed>, remove_after: int, automatic_redirects: array<string, bool>} $config */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        $container->setParameter('setono_sylius_redirect.remove_after', $config['remove_after']);

        $automaticRedirects = array_filter($config['automatic_redirects']);
        $container->setParameter('setono_sylius_redirect.automatic_redirects', $automaticRedirects);

        $loader->load('services.xml');

        $this->registerResources('setono_sylius_redirect', $config['driver'], $config['resources'], $container);
    }
}
