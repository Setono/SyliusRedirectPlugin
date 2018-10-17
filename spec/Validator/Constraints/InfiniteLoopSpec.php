<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use Setono\SyliusRedirectPlugin\Validator\Constraints\InfiniteLoopValidator;
use Symfony\Component\Validator\Constraint;

final class InfiniteLoopSpec extends ObjectBehavior
{
    public function it_is_constraint(): void
    {
        $this->shouldHaveType(Constraint::class);
    }

    public function it_is_only_a_class_constraint(): void
    {
        $this->getTargets()->shouldEqual(Constraint::CLASS_CONSTRAINT);
    }

    public function it_is_validated_by_service(): void
    {
        $this->validatedBy()->shouldReturn(InfiniteLoopValidator::class);
    }
}
