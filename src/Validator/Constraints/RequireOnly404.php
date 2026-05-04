<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class RequireOnly404 extends Constraint
{
    public string $message = 'setono_sylius_redirect.form.redirect.only_404.required_when_non_404_redirects_disabled';

    public function getTargets(): string
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}
