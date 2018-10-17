<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class Source extends Constraint
{
    /**
     * @var string
     */
    public $message = 'setono_sylius_redirect.form.errors.source_already_existing';

    /**
     * @return string
     */
    public function validatedBy(): string
    {
        return 'setono_sylius_redirect.validator.source_validator';
    }
}
