<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class InfiniteLoopValidator extends ConstraintValidator
{
    /**
     * @var RedirectRepositoryInterface
     */
    private $redirectRepository;

    /**
     * @param RedirectRepositoryInterface $redirectRepository
     */
    public function __construct(RedirectRepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @param RedirectInterface|null $value
     * @param Constraint|InfiniteLoop $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof InfiniteLoop || null === $value) {
            return;
        }

        if (!$value instanceof RedirectInterface) {
            throw new UnexpectedTypeException($value, RedirectInterface::class);
        }

        if (!$value->isEnabled()) {
            return;
        }

        $nextRedirect = $this->redirectRepository->searchNextRedirect($value);
        while ($nextRedirect instanceof RedirectInterface) {
            if ($nextRedirect->getDestination() === $value->getSource()) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('destination')
                    ->addViolation();

                break;
            }
            $nextRedirect = $this->redirectRepository->searchNextRedirect($nextRedirect);
        }
    }
}
