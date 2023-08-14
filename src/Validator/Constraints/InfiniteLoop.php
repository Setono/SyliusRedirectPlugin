<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class InfiniteLoop extends Constraint
{
    public string $message = 'The path creates an infinite loop';

    public function getTargets(): string
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}
