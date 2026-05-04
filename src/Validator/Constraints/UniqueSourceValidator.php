<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniqueSourceValidator extends ConstraintValidator
{
    public function __construct(private readonly RedirectRepositoryInterface $redirectRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        if (!$constraint instanceof UniqueSource) {
            throw new UnexpectedTypeException($constraint, UniqueSource::class);
        }

        if (!$value instanceof RedirectInterface) {
            throw new UnexpectedValueException($value, RedirectInterface::class);
        }

        $source = $value->getSource();
        if (null === $source || !$value->isEnabled() || !$value->getChannels()->isEmpty()) {
            return;
        }

        $conflictingRedirect = $this->redirectRepository->findOneEnabledBySource($source);
        if (null === $conflictingRedirect || $value->getId() === $conflictingRedirect->getId()) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath('source')
            ->setParameter('{{ source }}', $source)
            ->setParameter('{{ conflictingId }}', (string) $conflictingRedirect->getId())
            ->addViolation();
    }
}
