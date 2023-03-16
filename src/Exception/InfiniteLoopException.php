<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Exception;

use RuntimeException;

final class InfiniteLoopException extends RuntimeException implements ExceptionInterface
{
    public function __construct(string $source)
    {
        parent::__construct(sprintf('The source "%s" returns an infinite loop of redirects', $source));
    }
}
