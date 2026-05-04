<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusRedirectPlugin\Controller\Admin\CheckSourceAction;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CheckSourceActionTest extends TestCase
{
    use ProphecyTrait;

    public function test_it_reports_no_match_when_source_is_blank(): void
    {
        $repository = $this->prophesize(RedirectRepositoryInterface::class);
        $repository->findOneBySource(\Prophecy\Argument::any())->shouldNotBeCalled();

        $action = new CheckSourceAction(
            $repository->reveal(),
            $this->prophesize(UrlGeneratorInterface::class)->reveal(),
        );

        $response = $action(new Request(['source' => '   ']));

        self::assertSame('{"exists":false}', $response->getContent());
    }

    public function test_it_reports_no_match_when_repository_returns_null(): void
    {
        $repository = $this->prophesize(RedirectRepositoryInterface::class);
        $repository->findOneBySource('/foo')->willReturn(null);

        $action = new CheckSourceAction(
            $repository->reveal(),
            $this->prophesize(UrlGeneratorInterface::class)->reveal(),
        );

        $response = $action(new Request(['source' => '/foo']));

        self::assertSame('{"exists":false}', $response->getContent());
    }

    public function test_it_reports_no_match_when_the_only_match_is_excluded_by_id(): void
    {
        $existing = $this->prophesize(RedirectInterface::class);
        $existing->getId()->willReturn(7);

        $repository = $this->prophesize(RedirectRepositoryInterface::class);
        $repository->findOneBySource('/foo')->willReturn($existing->reveal());

        $action = new CheckSourceAction(
            $repository->reveal(),
            $this->prophesize(UrlGeneratorInterface::class)->reveal(),
        );

        $response = $action(new Request(['source' => '/foo', 'excludeId' => '7']));

        self::assertSame('{"exists":false}', $response->getContent());
    }

    public function test_it_reports_a_conflict_with_full_payload(): void
    {
        $existing = $this->prophesize(RedirectInterface::class);
        $existing->getId()->willReturn(42);
        $existing->getSource()->willReturn('/foo');
        $existing->getDestination()->willReturn('/bar');

        $repository = $this->prophesize(RedirectRepositoryInterface::class);
        $repository->findOneBySource('/foo')->willReturn($existing->reveal());

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate('setono_sylius_redirect_admin_redirect_update', ['id' => 42])
            ->willReturn('/admin/redirects/42/edit');

        $action = new CheckSourceAction($repository->reveal(), $urlGenerator->reveal());

        $response = $action(new Request(['source' => '/foo']));

        self::assertSame(
            '{"exists":true,"id":42,"source":"\/foo","destination":"\/bar","editUrl":"\/admin\/redirects\/42\/edit"}',
            $response->getContent(),
        );
    }
}
