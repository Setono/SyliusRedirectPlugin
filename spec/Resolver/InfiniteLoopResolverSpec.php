<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\Resolver;

use PhpSpec\ObjectBehavior;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Setono\SyliusRedirectPlugin\Resolver\InfiniteLoopResolverInterface;

final class InfiniteLoopResolverSpec extends ObjectBehavior
{
    public function let(RedirectRepositoryInterface $redirectRepository): void
    {
        $this->beConstructedWith($redirectRepository);
    }

    public function it_is_infinite_loop_resolver(): void
    {
        $this->shouldHaveType(InfiniteLoopResolverInterface::class);
    }

    public function it_returns_conflicting_redirect(
        RedirectRepositoryInterface $redirectRepository,
        RedirectInterface $subject,
        RedirectInterface $nextRedirect
    ): void {
        $subject->isEnabled()->willReturn(true);
        $subject->getSource()->willReturn('/source');

        $redirectRepository->searchNextRedirect($subject)->willReturn($nextRedirect);
        $nextRedirect->getDestination()->willReturn('/source');

        $this->getConflictingRedirect($subject)->shouldReturn($nextRedirect);
    }
}
