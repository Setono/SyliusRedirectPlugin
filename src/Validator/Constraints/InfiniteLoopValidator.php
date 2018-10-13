<?php

declare(strict_types = 1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class InfiniteLoopValidator extends ConstraintValidator
{
    /** @var RepositoryInterface|RedirectRepositoryInterface */
    private $redirectionRepository;
    
    /**
     * InfiniteLoopValidator constructor.
     *
     * @param RepositoryInterface $redirectionRepository
     */
    public function __construct(RepositoryInterface $redirectionRepository)
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
        
        $nextRedirection = $this->redirectionRepository->searchNextRedirection($redirection);
        while ($nextRedirection instanceof RedirectInterface) {
            if ($nextRedirection->getDestination() === $redirection->getSource()) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('destination')
                    ->addViolation();
                
                break;
            }
            $nextRedirection = $this->redirectionRepository->searchNextRedirection($nextRedirection);
        }
    }
}