<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\UrlResolver;

use Sylius\Component\Product\Model\ProductInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class ProductAutomaticRedirectUrlResolver implements AutomaticRedirectUrlResolverInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function supports(string $class): bool
    {
        return is_a($class, ProductInterface::class, true);
    }

    public function resolve(object $resource, string $slug, string $locale): string
    {
        return $this->urlGenerator->generate('sylius_shop_product_show', [
            'slug' => $slug,
            '_locale' => $locale,
        ]);
    }
}
