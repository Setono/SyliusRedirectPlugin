<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniqueSourcePerChannelValidator extends ConstraintValidator
{
    public function __construct(private readonly RedirectRepositoryInterface $redirectRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        if (!$constraint instanceof UniqueSourcePerChannel) {
            throw new UnexpectedTypeException($constraint, UniqueSourcePerChannel::class);
        }

        if (!$value instanceof RedirectInterface) {
            throw new UnexpectedValueException($value, RedirectInterface::class);
        }

        $source = $value->getSource();
        if (null === $source || !$value->isEnabled() || $value->getChannels()->isEmpty()) {
            return;
        }

        foreach ($value->getChannels() as $channel) {
            $conflictingRedirect = $this->redirectRepository->findOneEnabledBySource($source, $channel);
            if (null === $conflictingRedirect || $value->getId() === $conflictingRedirect->getId()) {
                continue;
            }

            $this->context->buildViolation($constraint->message)
                ->atPath('source')
                ->setParameter('{{ source }}', $source)
                ->setParameter('{{ channel }}', (string) $channel->getCode())
                ->setParameter('{{ conflictingId }}', (string) $conflictingRedirect->getId())
                ->addViolation();
        }
    }
}
