<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\UrlResolver;

use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class TaxonAutomaticRedirectUrlResolver implements AutomaticRedirectUrlResolverInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function supports(string $class): bool
    {
        return is_a($class, TaxonInterface::class, true);
    }

    public function resolve(object $resource, string $slug, string $locale): string
    {
        return $this->urlGenerator->generate('sylius_shop_product_index', [
            'slug' => $slug,
            '_locale' => $locale,
        ]);
    }
}
