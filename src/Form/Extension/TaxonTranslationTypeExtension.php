<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Form\Extension;

use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonTranslationType;

final class TaxonTranslationTypeExtension extends AutomaticRedirectTypeExtension
{
    public function getExtendedType(): string
    {
        return TaxonTranslationType::class;
    }

    public static function getExtendedTypes(): array
    {
        return [TaxonTranslationType::class];
    }
}
