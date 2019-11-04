<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class SourceRegexValidator extends RegexValidator
{
    /** @var string */
    private $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = '#' . str_replace('#', '\#', $pattern) . '#';
    }

    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!$constraint instanceof SourceRegex) {
            throw new UnexpectedTypeException($constraint, SourceRegex::class);
        }

        $constraint->pattern = $this->pattern;

        parent::validate($value, $constraint);
    }
}
