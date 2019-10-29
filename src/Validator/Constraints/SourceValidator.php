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

    public function __construct(RedirectRepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Source || null === $value) {
            return;
        }

        if (!$value instanceof RedirectInterface) {
            throw new UnexpectedTypeException($value, RedirectInterface::class);
        }

        if (!$value->isEnabled()) {
            return;
        }

        /** @var RedirectInterface[] $conflictingRedirects */
        $conflictingRedirects = $this->redirectRepository->findBy(['source' => $value->getSource(), 'enabled' => true]);
        $conflictingRedirects = array_filter($conflictingRedirects, static function (RedirectInterface $conflictingRedirect) use ($value): bool {
            return $conflictingRedirect->getId() !== $value->getId();
        });

        if (count($conflictingRedirects) > 0) {
            $conflictingIds = implode(
                ', ',
                array_map(static function (RedirectInterface $item) {
                    return $item->getId();
                }, $conflictingRedirects)
            );

            $this->context->buildViolation($constraint->message)
                ->atPath('source')
                ->setParameter('{{ source }}', $value->getSource())
                ->setParameter('{{ conflictingIds }}', $conflictingIds)
                ->addViolation();
        }
    }
}
