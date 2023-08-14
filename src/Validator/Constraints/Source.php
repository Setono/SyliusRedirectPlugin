<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class Source extends Constraint
{
    public string $message = 'There is already a redirection with source "{{ source }}". Redirection ID : {{ conflictingId }}';

    public function getTargets(): string
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}
