<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusRedirectPlugin\Exception\InfiniteLoopException;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolver;
use Sylius\Component\Channel\Model\Channel;
use Symfony\Component\HttpFoundation\Request;

final class RedirectionPathResolverTest extends TestCase
{
    use ProphecyTrait;

    public function test_it_returns_an_empty_path_when_no_redirect_matches(): void
    {
        $repository = $this->prophesize(RedirectRepositoryInterface::class);
        $repository->findOneEnabledBySource('/foo', null, false)->willReturn(null);

        $resolver = new RedirectionPathResolver($repository->reveal());

        $path = $resolver->resolve('/foo');

        self::assertTrue($path->isEmpty());
    }

    public function test_it_walks_a_chain_of_redirects(): void
    {
        $first = $this->createRedirect(1, '/a', '/b', false);
        $second = $this->createRedirect(2, '/b', '/c', false);

        $repository = $this->prophesize(RedirectRepositoryInterface::class);
        $repository->findOneEnabledBySource('/a', null, false)->willReturn($first);
        $repository->findOneEnabledBySource('/b', null, false)->willReturn($second);
        $repository->findOneEnabledBySource('/c', null, false)->willReturn(null);

        $resolver = new RedirectionPathResolver($repository->reveal());

        $path = $resolver->resolve('/a');

        self::assertSame([$first, $second], $path->all());
    }

    public function test_it_stops_walking_when_a_redirect_is_only_404(): void
    {
        $first = $this->createRedirect(1, '/a', '/b', true);
        // never reached because the first redirect is only404 = true
        $unreachable = $this->createRedirect(2, '/b', '/c', false);

        $repository = $this->prophesize(RedirectRepositoryInterface::class);
        $repository->findOneEnabledBySource('/a', null, false)->willReturn($first);
        $repository->findOneEnabledBySource('/b', null, false)->willReturn($unreachable)->shouldNotBeCalled();

        $resolver = new RedirectionPathResolver($repository->reveal());

        $path = $resolver->resolve('/a');

        self::assertSame([$first], $path->all());
    }

    public function test_it_throws_an_infinite_loop_exception_when_a_cycle_is_detected(): void
    {
        $first = $this->createRedirect(1, '/a', '/b', false);
        $second = $this->createRedirect(2, '/b', '/a', false);

        $repository = $this->prophesize(RedirectRepositoryInterface::class);
        $repository->findOneEnabledBySource('/a', null, false)->willReturn($first);
        $repository->findOneEnabledBySource('/b', null, false)->willReturn($second);

        $resolver = new RedirectionPathResolver($repository->reveal());

        $this->expectException(InfiniteLoopException::class);
        $this->expectExceptionMessage('The source "/a" returns an infinite loop of redirects');

        $resolver->resolve('/a');
    }

    public function test_it_passes_the_channel_and_only404_arguments_through(): void
    {
        $channel = new Channel();
        $channel->setCode('WEB');

        $repository = $this->prophesize(RedirectRepositoryInterface::class);
        $repository->findOneEnabledBySource('/foo', $channel, true)->willReturn(null)->shouldBeCalled();

        $resolver = new RedirectionPathResolver($repository->reveal());

        $resolver->resolve('/foo', $channel, true);
    }

    public function test_resolve_from_request_uses_the_path_info(): void
    {
        $repository = $this->prophesize(RedirectRepositoryInterface::class);
        $repository->findOneEnabledBySource('/some/path', null, false)->willReturn(null)->shouldBeCalled();

        $resolver = new RedirectionPathResolver($repository->reveal());

        $resolver->resolveFromRequest(Request::create('/some/path'));
    }

    private function createRedirect(int $id, string $source, string $destination, bool $only404): RedirectInterface
    {
        $redirect = $this->prophesize(RedirectInterface::class);
        $redirect->getId()->willReturn($id);
        $redirect->getSource()->willReturn($source);
        $redirect->getDestination()->willReturn($destination);
        $redirect->isOnly404()->willReturn($only404);

        return $redirect->reveal();
    }
}
