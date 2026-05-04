<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin;

use Setono\CompositeCompilerPass\CompositeCompilerPass;
use Setono\SyliusRedirectPlugin\DependencyInjection\Compiler\ConfigureAutomaticRedirectsPass;
use Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolver;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusRedirectPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CompositeCompilerPass(
            AutomaticRedirectUrlResolver::class,
            'setono_sylius_redirect.automatic_redirect_url_resolver',
        ));

        // Priority -10 (lower than the default 0) ensures this pass runs AFTER Sylius's
        // RegisterResourcesPass has populated the `sylius.resources` parameter; we read
        // that parameter here to validate the configured aliases and to wire kernel.event_listener
        // tags onto the AutomaticRedirectSubscriber for each enabled alias.
        $container->addCompilerPass(new ConfigureAutomaticRedirectsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10);
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getSupportedDrivers(): array
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }

    protected function getModelNamespace(): string
    {
        return 'Setono\SyliusRedirectPlugin\Model';
    }

    protected function getConfigFilesPath(): string
    {
        return sprintf(
            '%s/config/doctrine/%s',
            $this->getPath(),
            strtolower($this->getDoctrineMappingDirectory()),
        );
    }
}
