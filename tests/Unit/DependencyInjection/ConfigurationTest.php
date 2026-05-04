<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusRedirectPlugin\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    public function test_minimal_config_is_valid(): void
    {
        $this->assertConfigurationIsValid([[]]);
    }

    public function test_automatic_redirects_defaults_to_an_empty_associative_array(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[]],
            ['automatic_redirects' => []],
            'automatic_redirects',
        );
    }

    public function test_automatic_redirects_accepts_per_alias_booleans(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['automatic_redirects' => ['sylius.product' => true, 'sylius.taxon' => false]]],
            ['automatic_redirects' => ['sylius.product' => true, 'sylius.taxon' => false]],
            'automatic_redirects',
        );
    }

    public function test_automatic_redirects_rejects_non_boolean_values(): void
    {
        $this->assertConfigurationIsInvalid(
            [['automatic_redirects' => ['sylius.product' => 'yes']]],
            'automatic_redirects',
        );
    }

    public function test_remove_after_defaults_to_zero(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[]],
            ['remove_after' => 0],
            'remove_after',
        );
    }

    public function test_allow_non_404_redirects_defaults_to_true(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[]],
            ['allow_non_404_redirects' => true],
            'allow_non_404_redirects',
        );
    }

    public function test_allow_non_404_redirects_can_be_disabled(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['allow_non_404_redirects' => false]],
            ['allow_non_404_redirects' => false],
            'allow_non_404_redirects',
        );
    }

    public function test_allow_non_404_redirects_rejects_non_boolean_values(): void
    {
        $this->assertConfigurationIsInvalid(
            [['allow_non_404_redirects' => 'yes']],
            'allow_non_404_redirects',
        );
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
