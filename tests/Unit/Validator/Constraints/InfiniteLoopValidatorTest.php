<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\Validator\Constraints;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusRedirectPlugin\Exception\InfiniteLoopException;
use Setono\SyliusRedirectPlugin\Model\Redirect;
use Setono\SyliusRedirectPlugin\Model\RedirectionPath;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Setono\SyliusRedirectPlugin\Validator\Constraints\InfiniteLoop;
use Setono\SyliusRedirectPlugin\Validator\Constraints\InfiniteLoopValidator;
use stdClass;
use Sylius\Component\Channel\Model\Channel;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class InfiniteLoopValidatorTest extends ConstraintValidatorTestCase
{
    use ProphecyTrait;

    private ChannelRepositoryInterface $channelRepository;

    private RedirectionPathResolverInterface $resolver;

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->channelRepository = $this->prophesize(ChannelRepositoryInterface::class)->reveal();
        $this->resolver = $this->prophesize(RedirectionPathResolverInterface::class)->reveal();

        return new InfiniteLoopValidator($this->channelRepository, $this->resolver);
    }

    public function test_it_does_nothing_when_value_is_null(): void
    {
        $this->validator->validate(null, new InfiniteLoop());

        $this->assertNoViolation();
    }

    public function test_it_throws_when_constraint_is_not_supported(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Redirect(), new NotNull());
    }

    public function test_it_throws_when_value_is_not_a_redirect(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(new stdClass(), new InfiniteLoop());
    }

    public function test_it_does_nothing_when_source_is_missing(): void
    {
        $redirect = new Redirect();
        $redirect->setEnabled(true);

        $this->validator->validate($redirect, new InfiniteLoop());

        $this->assertNoViolation();
    }

    public function test_it_does_nothing_when_redirect_is_disabled(): void
    {
        $redirect = new Redirect();
        $redirect->setSource('/source');
        $redirect->setEnabled(false);

        $this->validator->validate($redirect, new InfiniteLoop());

        $this->assertNoViolation();
    }

    public function test_it_does_not_add_a_violation_when_no_loop_is_detected(): void
    {
        $channelRepository = $this->prophesize(ChannelRepositoryInterface::class);
        $channelRepository->findAll()->willReturn([new Channel()]);

        $resolver = $this->prophesize(RedirectionPathResolverInterface::class);
        $resolver->resolve('/source', \Prophecy\Argument::any())->willReturn(new RedirectionPath());
        $resolver->resolve('/source', \Prophecy\Argument::any(), true)->willReturn(new RedirectionPath());

        $this->validator = new InfiniteLoopValidator($channelRepository->reveal(), $resolver->reveal());
        $this->validator->initialize($this->context);

        $redirect = new Redirect();
        $redirect->setSource('/source');
        $redirect->setEnabled(true);

        $this->validator->validate($redirect, new InfiniteLoop());

        $this->assertNoViolation();
    }

    public function test_it_adds_a_violation_at_destination_when_a_loop_is_detected(): void
    {
        $channel = new Channel();

        $channelRepository = $this->prophesize(ChannelRepositoryInterface::class);
        $channelRepository->findAll()->willReturn([$channel]);

        $resolver = $this->prophesize(RedirectionPathResolverInterface::class);
        $resolver->resolve('/source', $channel)->willThrow(new InfiniteLoopException('/source'));

        $this->validator = new InfiniteLoopValidator($channelRepository->reveal(), $resolver->reveal());
        $this->validator->initialize($this->context);

        $redirect = new Redirect();
        $redirect->setSource('/source');
        $redirect->setEnabled(true);

        $constraint = new InfiniteLoop();

        $this->validator->validate($redirect, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.destination')
            ->assertRaised();
    }
}
