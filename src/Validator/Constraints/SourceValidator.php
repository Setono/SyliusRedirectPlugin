<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class SourceValidator extends ConstraintValidator
{
    /** @var RedirectRepositoryInterface */
    private $redirectRepository;

    /**
     * SourceValidator constructor.
     *
     * @param RedirectRepositoryInterface $redirectRepository
     */
    public function __construct(RedirectRepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @param mixed             $value
     * @param Constraint|Source $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        /** @var RedirectInterface|null $redirection */
        $redirect = $this->context->getObject();
        if (!$redirect->isEnabled()) {
            return;
        }

        /** @var array|RedirectInterface[] $conflictingRedirects */
        $conflictingRedirects = $this->redirectRepository->findBy(['source' => $value, 'enabled' => true]);
        if ($redirect !== null && $redirect->getId() !== null) {
            foreach ($conflictingRedirects as $key => $conflictingRedirect) {
                if ($conflictingRedirect->getId() === $redirection->getId()) {
                    unset($conflictingRedirects[$key]);
                    $conflictingRedirects = array_values($conflictingRedirects);

                    break;
                }
            }
        }
        if (!empty($conflictingRedirects)) {
            $conflictingIds = '';
            foreach ($conflictingRedirects as $key => $conflictingRedirect) {
                if ($key) {
                    $conflictingIds .= ', ';
                }
                $conflictingIds .= $conflictingRedirect->getId();
            }
            $this->context->buildViolation($constraint->message)
                ->atPath('source')
                ->setParameter('{{ source }}', $value)
                ->setParameter('{{ conflictingIds }}', $conflictingIds)
                ->addViolation();
        }
    }
}
