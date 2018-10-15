<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class InfiniteLoopValidator extends ConstraintValidator
{
    /** @var RedirectRepositoryInterface */
    private $redirectRepository;

    /**
     * InfiniteLoopValidator constructor.
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
        if (!$constraint instanceof Source) {
            return;
        }
        
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        /** @var RedirectInterface|null $redirection */
        $redirection = $this->context->getObject();
        if (!$redirection instanceof RedirectInterface) {
            return;
        }
        
        if (!$redirection->isEnabled()) {
            return;
        }

        $nextRedirection = $this->redirectRepository->searchNextRedirect($redirection);
        while ($nextRedirection instanceof RedirectInterface) {
            if ($nextRedirection->getDestination() === $redirection->getSource()) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('destination')
                    ->addViolation();

                break;
            }
            $nextRedirection = $this->redirectRepository->searchNextRedirect($nextRedirection);
        }
    }
}
