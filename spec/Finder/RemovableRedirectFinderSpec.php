<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\Finder;

use PhpSpec\ObjectBehavior;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;

final class RemovableRedirectFinderSpec extends ObjectBehavior
{
    public function let(RedirectRepositoryInterface $redirectRepository, RedirectionPathResolverInterface $redirectionPathResolver): void
    {
        $this->beConstructedWith($redirectRepository, $redirectionPathResolver);
    }

    public function it_implements_redirect_finder_interface(): void
    {
        $this->shouldImplement(RemovableRedirectFinderInterface::class);
    }
}
