<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Setono\SyliusRedirectPlugin\Exception\InfiniteLoopException;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Setono\SyliusRedirectPlugin\Validator\Constraints\InfiniteLoop;
use stdClass;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class InfiniteLoopValidatorSpec extends ObjectBehavior
{
    public function let(
        ChannelRepositoryInterface $channelRepository,
        RedirectionPathResolverInterface $redirectionPathResolver,
        ExecutionContextInterface $context
    ): void {
        $this->beConstructedWith($channelRepository, $redirectionPathResolver);

        $this->initialize($context);
    }

    public function it_is_constraint_validator(): void
    {
        $this->shouldHaveType(ConstraintValidatorInterface::class);
    }

    public function it_does_not_apply_to_null_values(ExecutionContextInterface $context): void
    {
        $context->buildViolation(Argument::any())->shouldNotBeCalled();

        $this->validate(null, new InfiniteLoop());
    }

    public function it_throws_an_exception_if_subject_is_not_a_string(ExecutionContextInterface $context): void
    {
        $context->buildViolation(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(UnexpectedTypeException::class)->during('validate', [new stdClass(), new InfiniteLoop()]);
    }

    public function it_adds_violation_if_subject_will_create_infinite_loop(
        ExecutionContextInterface $context,
        ConstraintViolationBuilderInterface $violationBuilder,
        ChannelRepositoryInterface $channelRepository,
        ChannelInterface $channel,
        RedirectInterface $subject,
        RedirectionPathResolverInterface $redirectionPathResolver
    ): void {
        $subject->isEnabled()->willReturn(true);
        $subject->getSource()->willReturn('/source');

        $channelRepository->findAll()->willReturn([$channel]);

        $redirectionPathResolver->resolve('/source', $channel)->willThrow(InfiniteLoopException::class);

        $context
            ->buildViolation('setono_sylius_redirect.form.errors.target_result_in_infinite_loop')
            ->willReturn($violationBuilder);
        $violationBuilder->atPath('destination')->willReturn($violationBuilder);
        $violationBuilder->addViolation()->shouldBeCalled();

        $this->validate($subject, new InfiniteLoop());
    }

    public function it_does_not_add_violation_if_subject_is_disabled(
        ExecutionContextInterface $context,
        RedirectInterface $subject
    ): void {
        $subject->isEnabled()->willReturn(false);

        $context->buildViolation(Argument::any())->shouldNotBeCalled();

        $this->validate($subject, new InfiniteLoop());
    }
}
