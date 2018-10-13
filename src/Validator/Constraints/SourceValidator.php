<?php

declare(strict_types = 1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class SourceValidator extends ConstraintValidator
{
    /** @var RedirectRepositoryInterface */
    private $redirectionRepository;
    
    /**
     * SourceValidator constructor.
     *
     * @param RedirectRepositoryInterface $redirectionRepository
     */
    public function __construct(RedirectRepositoryInterface $redirectionRepository)
    {
        $this->redirectionRepository = $redirectionRepository;
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
        $redirection = $this->context->getObject();
        if (!$redirection->isEnabled()) {
            return;
        }
        
        /** @var array|RedirectInterface[] $conflictingRedirections */
        $conflictingRedirections = $this->redirectionRepository->findBy(['source' => $value, 'enabled' => true]);
        if ($redirection !== null && $redirection->getId() !== null) {
            foreach ($conflictingRedirections as $key => $conflictingRedirection) {
                if ($conflictingRedirection->getId() === $redirection->getId()) {
                    unset($conflictingRedirections[$key]);
                    $conflictingRedirections = array_values($conflictingRedirections);
                    break;
                }
            }
        }
        if (!empty($conflictingRedirections)) {
            $conflictingIds = '';
            foreach ($conflictingRedirections as $key => $conflictingRedirection) {
                if ($key) {
                    $conflictingIds .= ', ';
                }
                $conflictingIds .= $conflictingRedirection->getId();
            }
            $this->context->buildViolation($constraint->message)
                ->atPath('source')
                ->setParameter('{{ source }}', $value)
                ->setParameter('{{ conflictingIds }}', $conflictingIds)
                ->addViolation();
        }
    }
}