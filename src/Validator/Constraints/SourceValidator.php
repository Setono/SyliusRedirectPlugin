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
    /** @var RedirectRepositoryInterface */
    private $redirectRepository;

    public function __construct(RedirectRepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @param mixed $redirect
     */
    public function validate($redirect, Constraint $constraint): void
    {
        if (null === $redirect) {
            return;
        }

        if (!$constraint instanceof Source) {
            throw new UnexpectedTypeException($redirect, Source::class);
        }

        if (!$redirect instanceof RedirectInterface) {
            throw new UnexpectedTypeException($redirect, RedirectInterface::class);
        }

        if ($redirect->getSource() === null) {
            return;
        }

        if (!$redirect->isEnabled()) {
            return;
        }

        if ($redirect->getChannels()->isEmpty()) {
            $conflictingRedirect = $this->redirectRepository->findOneEnabledBySource($redirect->getSource());
            if (null === $conflictingRedirect || $redirect->getId() === $conflictingRedirect->getId()) {
                return;
            }

            $this->buildViolation($constraint, $redirect->getSource(), (int) $conflictingRedirect->getId());
        } else {
            foreach ($redirect->getChannels() as $channel) {
                $conflictingRedirect = $this->redirectRepository->findOneEnabledBySource($redirect->getSource(), $channel);
                if (null === $conflictingRedirect || $redirect->getId() === $conflictingRedirect->getId()) {
                    return;
                }

                $this->buildViolation($constraint, $redirect->getSource(), (int) $conflictingRedirect->getId());
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
