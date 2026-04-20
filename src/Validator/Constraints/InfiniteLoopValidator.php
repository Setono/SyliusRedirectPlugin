<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Validator\Constraints;

use Setono\SyliusRedirectPlugin\Exception\InfiniteLoopException;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class InfiniteLoopValidator extends ConstraintValidator
{
    public function __construct(private readonly ChannelRepositoryInterface $channelRepository, private readonly RedirectionPathResolverInterface $redirectionPathResolver)
    {
    }

    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        if (!$constraint instanceof InfiniteLoop) {
            throw new UnexpectedTypeException($value, InfiniteLoop::class);
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

        try {
            /** @var ChannelInterface $channel */
            foreach ($this->channelRepository->findAll() as $channel) {
                $this->redirectionPathResolver->resolve($source, $channel);
                $this->redirectionPathResolver->resolve($source, $channel, true);
            }
        } catch (InfiniteLoopException) {
            $this->context->buildViolation($constraint->message)
                ->atPath('destination')
                ->addViolation();
        }
    }
}
