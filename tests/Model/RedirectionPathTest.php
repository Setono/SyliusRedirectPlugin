<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusRedirectPlugin\Model\Redirect;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectionPath;

final class RedirectionPathTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_adds_redirects_in_the_correct_order_and_utility_methods_returns_correct_values(): void
    {
        $firstRedirect = new Redirect();
        $lastRedirect = new Redirect();
        $redirects = [$firstRedirect, $lastRedirect];

        $path = new RedirectionPath();

        foreach ($redirects as $redirect) {
            $path->addRedirect($redirect);
        }

        self::assertSame($redirects, $path->all());
        self::assertSame($firstRedirect, $path->first());
        self::assertSame($lastRedirect, $path->last());
        self::assertCount(2, $path);
    }

    #[Test]
    public function it_detects_cycle(): void
    {
        $redirect1 = $this->prophesize(RedirectInterface::class);
        $redirect1->getId()->willReturn(1);

        $redirect2 = $this->prophesize(RedirectInterface::class);
        $redirect2->getId()->willReturn(2);

        $redirect3 = $this->prophesize(RedirectInterface::class);
        $redirect3->getId()->willReturn(1);

        $redirects = [$redirect1->reveal(), $redirect2->reveal(), $redirect3->reveal()];

        $path = new RedirectionPath();

        foreach ($redirects as $redirect) {
            $path->addRedirect($redirect);
        }

        self::assertTrue($path->hasCycle());
    }

    #[Test]
    public function it_marks_all_redirects_as_accessed(): void
    {
        $redirect1 = $this->prophesize(RedirectInterface::class);
        $redirect1->getId()->willReturn(1);
        $redirect1->onAccess()->shouldBeCalled();

        $redirect2 = $this->prophesize(RedirectInterface::class);
        $redirect2->getId()->willReturn(1);
        $redirect2->onAccess()->shouldBeCalled();

        $redirects = [$redirect1->reveal(), $redirect2->reveal()];

        $path = new RedirectionPath();

        foreach ($redirects as $redirect) {
            $path->addRedirect($redirect);
        }

        $path->markAsAccessed();
    }
}
