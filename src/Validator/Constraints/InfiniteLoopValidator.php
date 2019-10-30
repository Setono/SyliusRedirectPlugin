<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Resolver\InfiniteLoopResolverInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class InfiniteLoopValidator extends ConstraintValidator
{
    /** @var InfiniteLoopResolverInterface */
    private $infiniteLoopResolver;

    public function __construct(InfiniteLoopResolverInterface $infiniteLoopResolver)
    {
        $this->infiniteLoopResolver = $infiniteLoopResolver;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof InfiniteLoop || null === $value) {
            return;
        }

        if (!$value instanceof RedirectInterface) {
            throw new UnexpectedTypeException($value, RedirectInterface::class);
        }

        if (!$value->isEnabled()) {
            return;
        }

        if ($this->infiniteLoopResolver->generatesInfiniteLoop($value)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('destination')
                ->addViolation();

            return;
        }
    }
}
