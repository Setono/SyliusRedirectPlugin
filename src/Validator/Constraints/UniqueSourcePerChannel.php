<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class UniqueSourcePerChannel extends Constraint
{
    public string $message = 'setono_sylius_redirect.form.redirect.source.unique_per_channel';

    public function getTargets(): string
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}
