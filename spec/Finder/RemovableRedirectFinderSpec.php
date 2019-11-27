<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\Finder;

use PhpSpec\ObjectBehavior;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;

final class RemovableRedirectFinderSpec extends ObjectBehavior
{
    public function let(RedirectionPathResolverInterface $redirectionPathResolver): void
    {
        $this->beConstructedWith($redirectionPathResolver);
    }

    public function it_implements_redirect_finder_interface(): void
    {
        $this->shouldImplement(RemovableRedirectFinderInterface::class);
    }
}
