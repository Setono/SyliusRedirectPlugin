<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\UrlResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusRedirectPlugin\UrlResolver\ProductAutomaticRedirectUrlResolver;
use stdClass;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Product\Model\ProductInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProductAutomaticRedirectUrlResolverTest extends TestCase
{
    use ProphecyTrait;

    public function test_it_supports_classes_implementing_product_interface(): void
    {
        $resolver = new ProductAutomaticRedirectUrlResolver(
            $this->prophesize(UrlGeneratorInterface::class)->reveal(),
        );

        self::assertTrue($resolver->supports(Product::class));
        self::assertTrue($resolver->supports(ProductInterface::class));
    }

    public function test_it_does_not_support_unrelated_classes(): void
    {
        $resolver = new ProductAutomaticRedirectUrlResolver(
            $this->prophesize(UrlGeneratorInterface::class)->reveal(),
        );

        self::assertFalse($resolver->supports(stdClass::class));
    }

    public function test_it_resolves_the_shop_product_show_url_for_the_given_slug_and_locale(): void
    {
        $product = new Product();

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate('sylius_shop_product_show', ['slug' => 'galactic-pulse-t-shirt', '_locale' => 'en_US'])
            ->shouldBeCalledOnce()
            ->willReturn('/en_US/products/galactic-pulse-t-shirt')
        ;

        $resolver = new ProductAutomaticRedirectUrlResolver($urlGenerator->reveal());

        self::assertSame(
            '/en_US/products/galactic-pulse-t-shirt',
            $resolver->resolve($product, 'galactic-pulse-t-shirt', 'en_US'),
        );
    }
}
