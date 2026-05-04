<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\Finder;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinder;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectionPath;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Sylius\Component\Channel\Model\Channel;

final class RemovableRedirectFinderTest extends TestCase
{
    use ProphecyTrait;

    public function test_it_returns_an_empty_iterable_when_the_destination_is_unknown(): void
    {
        $resolver = $this->prophesize(RedirectionPathResolverInterface::class);
        $resolver->resolve('/dest', null)->willReturn(new RedirectionPath());

        $finder = new RemovableRedirectFinder($resolver->reveal());

        $redirect = $this->createRedirectWithDestination('/dest');

        self::assertSame([], iterator_to_array($finder->findRedirectsTargetedBy($redirect)));
    }

    public function test_it_returns_the_first_redirect_of_the_resolved_path_for_a_global_redirect(): void
    {
        $existing = $this->prophesize(RedirectInterface::class);
        $existing->getId()->willReturn(11);

        $path = new RedirectionPath();
        $path->addRedirect($existing->reveal());

        $resolver = $this->prophesize(RedirectionPathResolverInterface::class);
        $resolver->resolve('/dest', null)->willReturn($path);

        $finder = new RemovableRedirectFinder($resolver->reveal());

        $result = iterator_to_array($finder->findRedirectsTargetedBy($this->createRedirectWithDestination('/dest')));

        self::assertCount(1, $result);
        self::assertSame($existing->reveal(), $result[0]);
    }

    public function test_it_resolves_per_channel_and_dedupes_repeated_matches(): void
    {
        $web = new Channel();
        $web->setCode('WEB');
        $mobile = new Channel();
        $mobile->setCode('MOBILE');

        $shared = $this->prophesize(RedirectInterface::class);
        $shared->getId()->willReturn(11);
        $shared->getSource()->willReturn('/dest');

        $webPath = new RedirectionPath();
        $webPath->addRedirect($shared->reveal());

        $mobilePath = new RedirectionPath();
        $mobilePath->addRedirect($shared->reveal());

        $resolver = $this->prophesize(RedirectionPathResolverInterface::class);
        $resolver->resolve('/dest', $web)->willReturn($webPath);
        $resolver->resolve('/dest', $mobile)->willReturn($mobilePath);

        $finder = new RemovableRedirectFinder($resolver->reveal());

        $redirect = $this->createRedirectWithDestination('/dest', [$web, $mobile]);

        $result = iterator_to_array($finder->findRedirectsTargetedBy($redirect));

        self::assertCount(1, $result, 'duplicate matches across channels collapse');
        self::assertSame($shared->reveal(), $result[0]);
    }

    public function test_it_includes_distinct_matches_per_channel(): void
    {
        $web = new Channel();
        $web->setCode('WEB');
        $mobile = new Channel();
        $mobile->setCode('MOBILE');

        $webMatch = $this->prophesize(RedirectInterface::class);
        $webMatch->getId()->willReturn(11);
        $mobileMatch = $this->prophesize(RedirectInterface::class);
        $mobileMatch->getId()->willReturn(22);

        $webPath = new RedirectionPath();
        $webPath->addRedirect($webMatch->reveal());

        $mobilePath = new RedirectionPath();
        $mobilePath->addRedirect($mobileMatch->reveal());

        $resolver = $this->prophesize(RedirectionPathResolverInterface::class);
        $resolver->resolve('/dest', $web)->willReturn($webPath);
        $resolver->resolve('/dest', $mobile)->willReturn($mobilePath);

        $finder = new RemovableRedirectFinder($resolver->reveal());

        $redirect = $this->createRedirectWithDestination('/dest', [$web, $mobile]);

        $result = iterator_to_array($finder->findRedirectsTargetedBy($redirect));

        self::assertCount(2, $result);
        self::assertSame([$webMatch->reveal(), $mobileMatch->reveal()], $result);
    }

    /**
     * @param list<Channel> $channels
     */
    private function createRedirectWithDestination(string $destination, array $channels = []): RedirectInterface
    {
        $redirect = $this->prophesize(RedirectInterface::class);
        $redirect->getDestination()->willReturn($destination);
        $redirect->getChannels()->willReturn(new ArrayCollection($channels));

        return $redirect->reveal();
    }
}
