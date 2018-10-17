<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Setono\SyliusRedirectPlugin\Validator\Constraints\InfiniteLoop;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class InfiniteLoopValidatorSpec extends ObjectBehavior
{
    public function let(ExecutionContextInterface $context, RedirectRepositoryInterface $redirectRepository): void
    {
        $this->beConstructedWith($redirectRepository);
        $this->initialize($context);
    }

    public function it_is_constraint_validator(): void
    {
        $this->shouldHaveType(ConstraintValidatorInterface::class);
    }

    public function it_does_not_apply_to_null_values(ExecutionContextInterface $context): void
    {
        $context->addViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validate(null, new InfiniteLoop());
    }

    public function it_throws_an_exception_if_subject_is_not_a_string(ExecutionContextInterface $context): void
    {
        $context->addViolation(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(UnexpectedTypeException::class)->during('validate', [new \stdClass(), new InfiniteLoop()]);
    }

    /*
    public function it_adds_violation_if_subject_will_create_infinite_loop(
        ExecutionContextInterface $context,
        RedirectRepositoryInterface $redirectRepository,
        RedirectInterface $subject
    ): void {
        $subject->isEnabled()->shouldBeCalled()->willReturn(true);
        $subject->getSource()->shouldBeCalled()->willReturn('/source');

        $context->addViolation(Argument::cetera())->shouldBeCalled();

        $this->validate($subject, new InfiniteLoop());
    }

    /*
    function it_does_not_add_violation_if_subject_is_enabled(
        ExecutionContextInterface $context,
        ToggleableInterface $subject
    ): void {
        $subject->isEnabled()->shouldBeCalled()->willReturn(true);

        $context->addViolation(Argument::cetera())->shouldNotBeCalled();

        $this->validate($subject, new Enabled());
    }
    */
}
