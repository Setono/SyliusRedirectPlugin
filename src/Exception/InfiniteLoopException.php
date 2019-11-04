<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Exception;

use RuntimeException;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;

final class InfiniteLoopException extends RuntimeException implements ExceptionInterface
{
    /**
     * @throws StringsException
     */
    public function __construct(string $source)
    {
        parent::__construct(sprintf('The source "%s" returns an infinite loop of redirects', $source));
    }
}
