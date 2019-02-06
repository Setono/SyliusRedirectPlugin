<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use Setono\SyliusRedirectPlugin\Validator\Constraints\SourceValidator;
use Symfony\Component\Validator\Constraint;

class SourceSpec extends ObjectBehavior
{
    function it_is_a_constraint(): void
    {
        $this->shouldHaveType(Constraint::class);
    }

    function it_is_only_a_property_constraint(): void
    {
        $this->getTargets()->shouldEqual(Constraint::CLASS_CONSTRAINT);
    }

    function it_is_validated_by_a_service(): void
    {
        $this->validatedBy()->shouldReturn(SourceValidator::class);
    }
}
