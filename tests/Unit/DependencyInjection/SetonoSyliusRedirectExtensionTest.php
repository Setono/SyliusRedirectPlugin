<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusRedirectPlugin\DependencyInjection\SetonoSyliusRedirectExtension;
use Setono\SyliusRedirectPlugin\EventSubscriber\NotFoundSubscriber;
use Setono\SyliusRedirectPlugin\EventSubscriber\RequestSubscriber;

final class SetonoSyliusRedirectExtensionTest extends AbstractExtensionTestCase
{
    public function test_remove_after_defaults_to_zero(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_redirect.remove_after', 0);
    }

    public function test_remove_after_uses_configured_value(): void
    {
        $this->load(['remove_after' => 90]);

        $this->assertContainerBuilderHasParameter('setono_sylius_redirect.remove_after', 90);
    }

    public function test_automatic_redirects_defaults_to_an_empty_array(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_redirect.automatic_redirects', []);
    }

    public function test_automatic_redirects_only_keeps_enabled_aliases(): void
    {
        $this->load([
            'automatic_redirects' => [
                'sylius.product' => true,
                'sylius.taxon' => false,
            ],
        ]);

        $this->assertContainerBuilderHasParameter(
            'setono_sylius_redirect.automatic_redirects',
            ['sylius.product' => true],
        );
    }

    public function test_allow_non_404_redirects_defaults_to_true_and_registers_request_subscriber(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_redirect.allow_non_404_redirects', true);
        $this->assertContainerBuilderHasService(RequestSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(RequestSubscriber::class, 'kernel.event_subscriber');
        $this->assertContainerBuilderHasService(NotFoundSubscriber::class);
    }

    public function test_disabling_allow_non_404_redirects_omits_the_request_subscriber(): void
    {
        $this->load(['allow_non_404_redirects' => false]);

        $this->assertContainerBuilderHasParameter('setono_sylius_redirect.allow_non_404_redirects', false);
        self::assertFalse(
            $this->container->hasDefinition(RequestSubscriber::class),
            'RequestSubscriber must not be registered when allow_non_404_redirects is false',
        );
        $this->assertContainerBuilderHasService(NotFoundSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(NotFoundSubscriber::class, 'kernel.event_subscriber');
    }

    protected function getContainerExtensions(): array
    {
        return [new SetonoSyliusRedirectExtension()];
    }
}
