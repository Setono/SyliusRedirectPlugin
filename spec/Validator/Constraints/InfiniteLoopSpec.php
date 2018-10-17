<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use Symfony\Component\Validator\Constraint;

final class InfiniteLoopSpec extends ObjectBehavior
{
    function it_is_constraint(): void
    {
        $this->shouldHaveType(Constraint::class);
    }

    function it_is_a_property_constraint(): void
    {
        $this->getTargets()->shouldContain(Constraint::PROPERTY_CONSTRAINT);
    }

    function it_is_not_a_class_constraint(): void
    {
        $this->getTargets()->shouldNotContain(Constraint::CLASS_CONSTRAINT);
    }

    /*
    function it_is_validated_by_service(): void
    {
        $this->validatedBy()->shouldReturn(EnabledValidator::class);
    }
    */
}
