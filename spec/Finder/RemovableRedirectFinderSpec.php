<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\Finder;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectionPath;
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

    public function it_finds_removable_redirects(RedirectInterface $testedRedirect,
                                                 RedirectInterface $aRedirect,
                                                 RedirectionPathResolverInterface $redirectionPathResolver
    ): void {
        $aRedirect->getSource()->willReturn('/a');
        $aRedirect->getChannels()->willReturn(new ArrayCollection());
        $testedRedirect->getDestination()->willReturn('/a');
        $testedRedirect->getChannels()->willReturn(new ArrayCollection());
        $testedRedirect->getId()->shouldBeCalled();

        $expectedPath = new RedirectionPath();
        $expectedPath->addRedirect($aRedirect->getWrappedObject());
        $redirectionPathResolver->resolve('/a', null)->willReturn($expectedPath);

        $this->findNextRedirect($testedRedirect)->shouldReturn(new ArrayCollection());
    }
}
