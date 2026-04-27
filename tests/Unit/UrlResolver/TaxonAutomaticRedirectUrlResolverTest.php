<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\UrlResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusRedirectPlugin\UrlResolver\TaxonAutomaticRedirectUrlResolver;
use stdClass;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TaxonAutomaticRedirectUrlResolverTest extends TestCase
{
    use ProphecyTrait;

    public function test_it_supports_classes_implementing_taxon_interface(): void
    {
        $resolver = new TaxonAutomaticRedirectUrlResolver(
            $this->prophesize(UrlGeneratorInterface::class)->reveal(),
        );

        self::assertTrue($resolver->supports(Taxon::class));
        self::assertTrue($resolver->supports(TaxonInterface::class));
    }

    public function test_it_does_not_support_unrelated_classes(): void
    {
        $resolver = new TaxonAutomaticRedirectUrlResolver(
            $this->prophesize(UrlGeneratorInterface::class)->reveal(),
        );

        self::assertFalse($resolver->supports(stdClass::class));
    }

    public function test_it_resolves_the_shop_product_index_url_for_the_given_slug_and_locale(): void
    {
        $taxon = new Taxon();

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate('sylius_shop_product_index', ['slug' => 'mens-collection', '_locale' => 'en_US'])
            ->shouldBeCalledOnce()
            ->willReturn('/en_US/taxons/mens-collection')
        ;

        $resolver = new TaxonAutomaticRedirectUrlResolver($urlGenerator->reveal());

        self::assertSame(
            '/en_US/taxons/mens-collection',
            $resolver->resolve($taxon, 'mens-collection', 'en_US'),
        );
    }
}
