<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Form\Extension;

use Sylius\Bundle\ProductBundle\Form\Type\ProductTranslationType;

final class ProductTranslationTypeExtension extends TranslationTypeExtension
{
    public function getExtendedType(): string
    {
        return ProductTranslationType::class;
    }

    public static function getExtendedTypes(): array
    {
        return [ProductTranslationType::class];
    }
}
