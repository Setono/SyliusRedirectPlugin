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

    /**
     * @param mixed $redirect
     */
    public function validate($redirect, Constraint $constraint): void
    {
        if (null === $redirect) {
            return;
        }

        if (!$constraint instanceof InfiniteLoop) {
            throw new UnexpectedTypeException($redirect, InfiniteLoop::class);
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

        try {
            /** @var ChannelInterface $channel */
            foreach ($this->channelRepository->findAll() as $channel) {
                $this->redirectionPathResolver->resolve($redirect->getSource(), $channel);
                $this->redirectionPathResolver->resolve($redirect->getSource(), $channel, true);
            }
        } catch (InfiniteLoopException $e) {
            $this->context->buildViolation($constraint->message)
                ->atPath('destination')
                ->addViolation();
        }
    }
}
