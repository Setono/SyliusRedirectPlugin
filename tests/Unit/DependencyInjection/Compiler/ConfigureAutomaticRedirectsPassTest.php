<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Setono\SyliusRedirectPlugin\DependencyInjection\Compiler\ConfigureAutomaticRedirectsPass;
use Setono\SyliusRedirectPlugin\EventListener\AutomaticRedirectListener;
use stdClass;
use Sylius\Component\Core\Model\Product;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class ConfigureAutomaticRedirectsPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ConfigureAutomaticRedirectsPass());
    }

    public function test_it_does_nothing_when_the_parameter_is_not_set(): void
    {
        $this->setDefinition(AutomaticRedirectListener::class, new Definition(AutomaticRedirectListener::class));

        $this->compile();

        self::assertSame(
            [],
            $this->container->getDefinition(AutomaticRedirectListener::class)->getTags(),
        );
    }

    public function test_it_does_nothing_when_no_aliases_are_enabled(): void
    {
        $this->container->setParameter('setono_sylius_redirect.automatic_redirects', []);
        $this->container->setParameter('sylius.resources', []);
        $this->setDefinition(AutomaticRedirectListener::class, new Definition(AutomaticRedirectListener::class));

        $this->compile();

        self::assertSame(
            [],
            $this->container->getDefinition(AutomaticRedirectListener::class)->getTags(),
        );
    }

    public function test_it_tags_the_listener_with_one_event_listener_per_enabled_alias(): void
    {
        $this->container->setParameter('setono_sylius_redirect.automatic_redirects', ['sylius.product' => true]);
        $this->container->setParameter('sylius.resources', [
            'sylius.product' => ['classes' => ['model' => Product::class]],
        ]);
        $this->setDefinition(AutomaticRedirectListener::class, new Definition(AutomaticRedirectListener::class));

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            AutomaticRedirectListener::class,
            'kernel.event_listener',
            ['event' => 'sylius.product.pre_update', 'method' => 'onPreUpdate'],
        );
    }

    public function test_it_throws_when_an_alias_is_not_a_known_sylius_resource(): void
    {
        $this->container->setParameter('setono_sylius_redirect.automatic_redirects', ['sylius.unknown' => true]);
        $this->container->setParameter('sylius.resources', [
            'sylius.product' => ['classes' => ['model' => Product::class]],
        ]);
        $this->setDefinition(AutomaticRedirectListener::class, new Definition(AutomaticRedirectListener::class));

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/sylius\.unknown/');

        $this->compile();
    }

    public function test_it_throws_when_the_alias_model_is_not_slug_aware(): void
    {
        $this->container->setParameter('setono_sylius_redirect.automatic_redirects', ['sylius.administrator' => true]);
        $this->container->setParameter('sylius.resources', [
            'sylius.administrator' => ['classes' => ['model' => stdClass::class]],
        ]);
        $this->setDefinition(AutomaticRedirectListener::class, new Definition(AutomaticRedirectListener::class));

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/sylius\.administrator/');
        $this->expectExceptionMessageMatches('/' . preg_quote(stdClass::class, '/') . '/');

        $this->compile();
    }
}
