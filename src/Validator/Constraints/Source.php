<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class Source extends Constraint
{
    /** @var string */
    public $message = 'setono_sylius_redirect.form.errors.source_already_existing';

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}
