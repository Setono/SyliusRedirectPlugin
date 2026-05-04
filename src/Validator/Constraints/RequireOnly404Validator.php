<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class RequireOnly404Validator extends ConstraintValidator
{
    public function __construct(private readonly bool $allowNon404Redirects)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RequireOnly404) {
            throw new UnexpectedTypeException($constraint, RequireOnly404::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof RedirectInterface) {
            throw new UnexpectedValueException($value, RedirectInterface::class);
        }

        if ($this->allowNon404Redirects) {
            return;
        }

        if ($value->isOnly404()) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath('only404')
            ->addViolation();
    }
}
