<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\Redirect;
use Setono\SyliusRedirectPlugin\Validator\Constraints\RequireOnly404;
use Setono\SyliusRedirectPlugin\Validator\Constraints\RequireOnly404Validator;
use stdClass;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class RequireOnly404ValidatorTest extends ConstraintValidatorTestCase
{
    private bool $allowNon404Redirects = false;

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new RequireOnly404Validator($this->allowNon404Redirects);
    }

    public function test_it_does_nothing_when_value_is_null(): void
    {
        $this->validator->validate(null, new RequireOnly404());

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

        $this->validator->validate(new stdClass(), new RequireOnly404());
    }

    public function test_it_does_nothing_when_non_404_redirects_are_allowed(): void
    {
        $this->allowNon404Redirects = true;
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $redirect = new Redirect();
        $redirect->setOnly404(false);

        $this->validator->validate($redirect, new RequireOnly404());

        $this->assertNoViolation();
    }

    public function test_it_does_nothing_when_only404_is_true(): void
    {
        $redirect = new Redirect();
        $redirect->setOnly404(true);

        $this->validator->validate($redirect, new RequireOnly404());

        $this->assertNoViolation();
    }

    public function test_it_adds_a_violation_when_only404_is_false_and_non_404_redirects_are_disabled(): void
    {
        $redirect = new Redirect();
        $redirect->setOnly404(false);

        $constraint = new RequireOnly404();

        $this->validator->validate($redirect, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.only404')
            ->assertRaised();
    }
}
