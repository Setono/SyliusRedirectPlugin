<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\Validator\Constraints;

use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionProperty;
use Setono\SyliusRedirectPlugin\Model\Redirect;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Setono\SyliusRedirectPlugin\Validator\Constraints\UniqueSourcePerChannel;
use Setono\SyliusRedirectPlugin\Validator\Constraints\UniqueSourcePerChannelValidator;
use stdClass;
use Sylius\Component\Channel\Model\Channel;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class UniqueSourcePerChannelValidatorTest extends ConstraintValidatorTestCase
{
    use ProphecyTrait;

    private \Prophecy\Prophecy\ObjectProphecy $redirectRepository;

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->redirectRepository = $this->prophesize(RedirectRepositoryInterface::class);

        return new UniqueSourcePerChannelValidator($this->redirectRepository->reveal());
    }

    public function test_it_does_nothing_when_value_is_null(): void
    {
        $this->validator->validate(null, new UniqueSourcePerChannel());

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

        $this->validator->validate(new stdClass(), new UniqueSourcePerChannel());
    }

    public function test_it_does_nothing_when_source_is_missing(): void
    {
        $redirect = new Redirect();
        $redirect->setEnabled(true);
        $redirect->addChannel($this->createChannel('WEB'));

        $this->validator->validate($redirect, new UniqueSourcePerChannel());

        $this->assertNoViolation();
    }

    public function test_it_does_nothing_when_redirect_is_disabled(): void
    {
        $redirect = $this->createRedirect('/source');
        $redirect->setEnabled(false);
        $redirect->addChannel($this->createChannel('WEB'));

        $this->validator->validate($redirect, new UniqueSourcePerChannel());

        $this->assertNoViolation();
    }

    public function test_it_does_nothing_when_redirect_has_no_channels(): void
    {
        $this->validator->validate($this->createRedirect('/source'), new UniqueSourcePerChannel());

        $this->assertNoViolation();
    }

    public function test_it_does_nothing_when_no_channel_has_a_conflict(): void
    {
        $web = $this->createChannel('WEB');
        $mobile = $this->createChannel('MOBILE');

        $redirect = $this->createRedirect('/source');
        $redirect->addChannel($web);
        $redirect->addChannel($mobile);

        $this->redirectRepository->findOneEnabledBySource('/source', $web)->willReturn(null);
        $this->redirectRepository->findOneEnabledBySource('/source', $mobile)->willReturn(null);

        $this->validator->validate($redirect, new UniqueSourcePerChannel());

        $this->assertNoViolation();
    }

    public function test_it_skips_the_redirect_itself(): void
    {
        $web = $this->createChannel('WEB');

        $redirect = $this->createRedirect('/source', 5);
        $redirect->addChannel($web);

        $this->redirectRepository->findOneEnabledBySource('/source', $web)->willReturn($redirect);

        $this->validator->validate($redirect, new UniqueSourcePerChannel());

        $this->assertNoViolation();
    }

    public function test_it_adds_a_violation_for_a_conflicting_channel(): void
    {
        $web = $this->createChannel('WEB');

        $redirect = $this->createRedirect('/source');
        $redirect->addChannel($web);

        $conflicting = $this->createRedirect('/source', 99);

        $this->redirectRepository->findOneEnabledBySource('/source', $web)->willReturn($conflicting);

        $constraint = new UniqueSourcePerChannel();

        $this->validator->validate($redirect, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.source')
            ->setParameter('{{ source }}', '/source')
            ->setParameter('{{ channel }}', 'WEB')
            ->setParameter('{{ conflictingId }}', '99')
            ->assertRaised();
    }

    public function test_it_continues_through_remaining_channels_after_a_clean_check(): void
    {
        $web = $this->createChannel('WEB');
        $mobile = $this->createChannel('MOBILE');

        $redirect = $this->createRedirect('/source');
        $redirect->addChannel($web);
        $redirect->addChannel($mobile);

        $conflicting = $this->createRedirect('/source', 99);

        $this->redirectRepository->findOneEnabledBySource('/source', $web)->willReturn(null);
        $this->redirectRepository->findOneEnabledBySource('/source', $mobile)->willReturn($conflicting);

        $constraint = new UniqueSourcePerChannel();

        $this->validator->validate($redirect, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.source')
            ->setParameter('{{ source }}', '/source')
            ->setParameter('{{ channel }}', 'MOBILE')
            ->setParameter('{{ conflictingId }}', '99')
            ->assertRaised();
    }

    public function test_it_raises_one_violation_per_conflicting_channel(): void
    {
        $web = $this->createChannel('WEB');
        $mobile = $this->createChannel('MOBILE');

        $redirect = $this->createRedirect('/source');
        $redirect->addChannel($web);
        $redirect->addChannel($mobile);

        $webConflict = $this->createRedirect('/source', 11);
        $mobileConflict = $this->createRedirect('/source', 22);

        $this->redirectRepository->findOneEnabledBySource('/source', $web)->willReturn($webConflict);
        $this->redirectRepository->findOneEnabledBySource('/source', $mobile)->willReturn($mobileConflict);

        $constraint = new UniqueSourcePerChannel();

        $this->validator->validate($redirect, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.source')
            ->setParameter('{{ source }}', '/source')
            ->setParameter('{{ channel }}', 'WEB')
            ->setParameter('{{ conflictingId }}', '11')
            ->buildNextViolation($constraint->message)
            ->atPath('property.path.source')
            ->setParameter('{{ source }}', '/source')
            ->setParameter('{{ channel }}', 'MOBILE')
            ->setParameter('{{ conflictingId }}', '22')
            ->assertRaised();
    }

    private function createRedirect(string $source, ?int $id = null): Redirect
    {
        $redirect = new Redirect();
        $redirect->setSource($source);
        $redirect->setEnabled(true);

        if (null !== $id) {
            $property = new ReflectionProperty(Redirect::class, 'id');
            $property->setValue($redirect, $id);
        }

        return $redirect;
    }

    private function createChannel(string $code): Channel
    {
        $channel = new Channel();
        $channel->setCode($code);

        return $channel;
    }
}
