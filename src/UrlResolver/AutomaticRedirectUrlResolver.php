<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\UrlResolver;

use RuntimeException;
use Setono\CompositeCompilerPass\CompositeService;

/**
 * @extends CompositeService<AutomaticRedirectUrlResolverInterface>
 */
final class AutomaticRedirectUrlResolver extends CompositeService implements AutomaticRedirectUrlResolverInterface
{
    public function supports(string $class): bool
    {
        foreach ($this->services as $resolver) {
            if ($resolver->supports($class)) {
                return true;
            }
        }

        return false;
    }

    public function resolve(object $resource, string $slug, string $locale): string
    {
        foreach ($this->services as $resolver) {
            if ($resolver->supports($resource::class)) {
                return $resolver->resolve($resource, $slug, $locale);
            }
        }

        throw new RuntimeException(sprintf(
            'No %s registered that supports class "%s"',
            AutomaticRedirectUrlResolverInterface::class,
            $resource::class,
        ));
    }
}
