<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Promise\ReturnPromise;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Setono\SyliusRedirectPlugin\Validator\Constraints\InfiniteLoop;
use Setono\SyliusRedirectPlugin\Validator\Constraints\Source;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class SourceValidatorSpec extends ObjectBehavior
{
    function it_is_constraint_valdiator(): void
    {
        $this->shouldImplement(ConstraintValidatorInterface::class);
    }

    function let(
        RedirectRepositoryInterface $redirectRepository,
        ExecutionContextInterface $context
    ): void {
        $this->beConstructedWith($redirectRepository);
        $this->initialize($context);
    }

    function it_does_not_validat_other_constraints(
        ExecutionContextInterface $context,
        RedirectInterface $value
    ): void {
        $context->buildViolation(Argument::any())->shouldNotBeCalled();

        $this->validate($value, new InfiniteLoop());
    }

    function it_does_not_validate_null_values(
        ExecutionContextInterface $context
    ): void {
        $context->buildViolation(Argument::any())->shouldNotBeCalled();

        $this->validate(null, new Source());
    }

    function it_throws_an_exception_if_the_value_is_no_redirect(
        ExecutionContextInterface $context
    ): void {
        $context->buildViolation(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(UnexpectedTypeException::class)
            ->during('validate', ['hello', new Source()]);
    }

    function it_does_not_validate_disabled_redirects(
        ExecutionContextInterface $context,
        RedirectInterface $redirect
    ): void {
        $redirect->isEnabled()->willReturn(false);

        $context->buildViolation(Argument::any())->shouldNotBeCalled();

        $this->validate($redirect, new Source());
    }

    function it_does_not_add_violation_if_there_is_no_other_redirect(
        ExecutionContextInterface $context,
        RedirectInterface $redirect,
        RedirectRepositoryInterface $redirectRepository
    ): void {
        $source = '/dumb-source';
        $redirect->isEnabled()->willReturn(true);
        $redirect->getSource()->willReturn($source);
        $redirect->isEnabled()->shouldBeCalled();
        $redirect->getSource()->shouldBeCalled();
        $redirect->getId()->shouldBeCalled();

        $redirectRepository->findBy(['source' => $source, 'enabled' => true])
            ->willReturn([$redirect]);

        $context->buildViolation(Argument::any())->shouldNotBeCalled();

        $this->validate($redirect, new Source());
    }

    function it_adds_a_violation_if_there_is_another_route_with_the_same_source(
        ExecutionContextInterface $context,
        RedirectInterface $redirect,
        RedirectInterface $conflictingRedirects,
        RedirectRepositoryInterface $redirectRepository,
        ConstraintViolationBuilderInterface $violationBuilder
    ): void {
        $source = '/some-route';
        $redirect->isEnabled()->willReturn(true);
        $redirect->getId()->willReturn(null);
        $redirect->getSource()->willReturn($source);

        $redirectRepository->findBy(['source' => $source, 'enabled' => true])
            ->willReturn([$redirect, $conflictingRedirects]);

        $conflictingRedirects->getId()->willReturn(1);

        $context->buildViolation('setono_sylius_redirect.form.errors.source_already_existing')->willReturn($violationBuilder);
        $violationBuilder->atPath('source')->willReturn($violationBuilder);
        $violationBuilder->setParameter('{{ source }}', '/some-route')->willReturn($violationBuilder);
        $violationBuilder->setParameter('{{ conflictingIds }}', '1')->willReturn($violationBuilder);
        $violationBuilder->addViolation()->shouldBeCalled();

        $this->validate($redirect, new Source());
    }
}
