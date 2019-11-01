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
    /** @var ChannelRepositoryInterface */
    private $channelRepository;

    /** @var RedirectionPathResolverInterface */
    private $redirectionPathResolver;

    public function __construct(
        ChannelRepositoryInterface $channelRepository,
        RedirectionPathResolverInterface $redirectionPathResolver
    ) {
        $this->channelRepository = $channelRepository;
        $this->redirectionPathResolver = $redirectionPathResolver;
    }

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

        try {
            /** @var ChannelInterface $channel */
            foreach ($this->channelRepository->findAll() as $channel) {
                $this->redirectionPathResolver->resolve($value->getSource(), $channel);
                $this->redirectionPathResolver->resolve($value->getSource(), $channel, true);
            }
        } catch (InfiniteLoopException $e) {
            $this->context->buildViolation($constraint->message)
                ->atPath('destination')
                ->addViolation();
        }
    }
}
