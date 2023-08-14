<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class SourceValidator extends ConstraintValidator
{
    private RedirectRepositoryInterface $redirectRepository;

    public function __construct(RedirectRepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        if (!$constraint instanceof Source) {
            throw new UnexpectedTypeException($value, Source::class);
        }

        if (!$value instanceof RedirectInterface) {
            throw new UnexpectedTypeException($value, RedirectInterface::class);
        }

        $source = $value->getSource();

        if (null === $source) {
            return;
        }

        if (!$value->isEnabled()) {
            return;
        }

        if ($value->getChannels()->isEmpty()) {
            $conflictingRedirect = $this->redirectRepository->findOneEnabledBySource($source);
            if (null === $conflictingRedirect || $value->getId() === $conflictingRedirect->getId()) {
                return;
            }

            $this->buildViolation($constraint, $source, (int) $conflictingRedirect->getId());
        } else {
            foreach ($value->getChannels() as $channel) {
                $conflictingRedirect = $this->redirectRepository->findOneEnabledBySource($source, $channel);
                if (null === $conflictingRedirect || $value->getId() === $conflictingRedirect->getId()) {
                    return;
                }

                $this->buildViolation($constraint, $source, (int) $conflictingRedirect->getId());
            }
        }
    }

    private function buildViolation(Source $constraint, string $source, int $conflictingId): void
    {
        $this->context->buildViolation($constraint->message)
            ->atPath('source')
            ->setParameter('{{ source }}', $source)
            ->setParameter('{{ conflictingId }}', (string) $conflictingId)
            ->addViolation();
    }
}
