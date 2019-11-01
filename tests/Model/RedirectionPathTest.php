<?php
declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusRedirectPlugin\Model\Redirect;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectionPath;

final class RedirectionPathTest extends TestCase
{
    /**
     * @test
     */
    public function it_adds_redirects_in_the_correct_order_and_utility_methods_returns_correct_values(): void
    {
        $firstRedirect = new Redirect();
        $lastRedirect = new Redirect();
        $redirects = [$firstRedirect, $lastRedirect];

        $path = new RedirectionPath();

        foreach ($redirects as $redirect) {
            $path->addRedirect($redirect);
        }

        $this->assertSame($redirects, $path->all());
        $this->assertSame($firstRedirect, $path->first());
        $this->assertSame($lastRedirect, $path->last());
        $this->assertCount(2, $path);
    }

    /**
     * @test
     */
    public function it_detects_cycle(): void
    {
        $redirect1 = $this->prophesize(RedirectInterface::class);
        $redirect1->getId()->willReturn(1);

        $redirect2 = $this->prophesize(RedirectInterface::class);
        $redirect2->getId()->willReturn(2);

        $redirect3 = $this->prophesize(RedirectInterface::class);
        $redirect3->getId()->willReturn(1); // returns the same id as the first redirect

        $redirects = [$redirect1->reveal(), $redirect2->reveal(), $redirect3->reveal()];

        $path = new RedirectionPath();

        foreach ($redirects as $redirect) {
            $path->addRedirect($redirect);
        }

        $this->assertTrue($path->hasCycle());
    }

    /**
     * @test
     */
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
