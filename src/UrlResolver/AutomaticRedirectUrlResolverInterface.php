<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\UrlResolver;

interface AutomaticRedirectUrlResolverInterface
{
    public function supports(string $class): bool;

    public function resolve(object $resource, string $slug, string $locale): string;
}
