<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\SlugUpdateHandler;

final class TaxonTranslationSlugUpdateHandler extends SlugUpdateHandler
{
    protected function generateUrl(string $slug, string $locale): string
    {
        return $this->urlGenerator->generate('sylius_shop_product_index', [
            'slug' => $slug,
            '_locale' => $locale,
        ]);
    }
}
