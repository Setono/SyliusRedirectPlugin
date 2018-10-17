<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class InfiniteLoop extends Constraint
{
    /**
     * @var string
     */
    public $message = 'setono_sylius_redirect.form.errors.target_result_in_infinite_loop';

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string
    {
        return Constraint::CLASS_CONSTRAINT;
    }
}
