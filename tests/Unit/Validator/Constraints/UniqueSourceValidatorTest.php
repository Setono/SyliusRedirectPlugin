<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\Validator\Constraints;

use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionProperty;
use Setono\SyliusRedirectPlugin\Model\Redirect;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Setono\SyliusRedirectPlugin\Validator\Constraints\UniqueSource;
use Setono\SyliusRedirectPlugin\Validator\Constraints\UniqueSourceValidator;
use stdClass;
use Sylius\Component\Channel\Model\Channel;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class UniqueSourceValidatorTest extends ConstraintValidatorTestCase
{
    use ProphecyTrait;

    private \Prophecy\Prophecy\ObjectProphecy $redirectRepository;

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->redirectRepository = $this->prophesize(RedirectRepositoryInterface::class);

        return new UniqueSourceValidator($this->redirectRepository->reveal());
    }

    public function test_it_does_nothing_when_value_is_null(): void
    {
        $this->validator->validate(null, new UniqueSource());

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

        $this->validator->validate(new stdClass(), new UniqueSource());
    }

    public function test_it_does_nothing_when_source_is_missing(): void
    {
        $redirect = new Redirect();
        $redirect->setEnabled(true);

        $this->validator->validate($redirect, new UniqueSource());

        $this->assertNoViolation();
    }

    public function test_it_does_nothing_when_redirect_is_disabled(): void
    {
        $redirect = $this->createRedirect('/source');
        $redirect->setEnabled(false);

        $this->validator->validate($redirect, new UniqueSource());

        $this->assertNoViolation();
    }

    public function test_it_does_nothing_when_redirect_has_channels(): void
    {
        $redirect = $this->createRedirect('/source');
        $redirect->addChannel(new Channel());

        $this->validator->validate($redirect, new UniqueSource());

        $this->assertNoViolation();
    }

    public function test_it_does_nothing_when_no_conflict_exists(): void
    {
        $this->redirectRepository->findOneEnabledBySource('/source')->willReturn(null);

        $this->validator->validate($this->createRedirect('/source'), new UniqueSource());

        $this->assertNoViolation();
    }

    public function test_it_does_nothing_when_the_only_match_is_the_redirect_itself(): void
    {
        $redirect = $this->createRedirect('/source', 7);

        $this->redirectRepository->findOneEnabledBySource('/source')->willReturn($redirect);

        $this->validator->validate($redirect, new UniqueSource());

        $this->assertNoViolation();
    }

    public function test_it_adds_a_violation_when_another_redirect_owns_the_source(): void
    {
        $redirect = $this->createRedirect('/source');
        $conflicting = $this->createRedirect('/source', 42);

        $this->redirectRepository->findOneEnabledBySource('/source')->willReturn($conflicting);

        $constraint = new UniqueSource();

        $this->validator->validate($redirect, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.source')
            ->setParameter('{{ source }}', '/source')
            ->setParameter('{{ conflictingId }}', '42')
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
}
