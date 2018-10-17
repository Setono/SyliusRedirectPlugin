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
     * @param mixed                   $value
     * @param Constraint|InfiniteLoop $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof InfiniteLoop) {
            return;
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        /** @var RedirectInterface|null $redirect */
        $redirect = $this->context->getObject();
        if (!$redirect instanceof RedirectInterface) {
            return;
        }

        if (!$redirect->isEnabled()) {
            return;
        }

        $nextRedirect = $this->redirectRepository->searchNextRedirect($redirect);
        while ($nextRedirect instanceof RedirectInterface) {
            if ($nextRedirect->getDestination() === $redirect->getSource()) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('destination')
                    ->addViolation();

                break;
            }
            $nextRedirect = $this->redirectRepository->searchNextRedirect($nextRedirect);
        }
    }
}
