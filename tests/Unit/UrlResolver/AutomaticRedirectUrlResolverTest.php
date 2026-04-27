<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\UrlResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use RuntimeException;
use Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolver;
use Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolverInterface;
use stdClass;

final class AutomaticRedirectUrlResolverTest extends TestCase
{
    use ProphecyTrait;

    public function test_it_delegates_to_the_first_child_that_supports_the_class(): void
    {
        $subject = new stdClass();

        $first = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $first->supports(stdClass::class)->willReturn(true);
        $first->resolve($subject, 'new-slug', 'en_US')->willReturn('/en/new-slug');

        $second = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $second->supports(stdClass::class)->shouldNotBeCalled();
        $second->resolve($subject, 'new-slug', 'en_US')->shouldNotBeCalled();

        $composite = new AutomaticRedirectUrlResolver();
        $composite->add($first->reveal());
        $composite->add($second->reveal());

        self::assertSame('/en/new-slug', $composite->resolve($subject, 'new-slug', 'en_US'));
    }

    public function test_it_skips_resolvers_that_do_not_support_the_class(): void
    {
        $subject = new stdClass();

        $first = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $first->supports(stdClass::class)->willReturn(false);
        $first->resolve($subject, 'new-slug', 'en_US')->shouldNotBeCalled();

        $second = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $second->supports(stdClass::class)->willReturn(true);
        $second->resolve($subject, 'new-slug', 'en_US')->willReturn('/en/new-slug');

        $composite = new AutomaticRedirectUrlResolver();
        $composite->add($first->reveal());
        $composite->add($second->reveal());

        self::assertSame('/en/new-slug', $composite->resolve($subject, 'new-slug', 'en_US'));
    }

    public function test_supports_returns_true_when_at_least_one_child_supports_the_class(): void
    {
        $first = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $first->supports(stdClass::class)->willReturn(false);

        $second = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $second->supports(stdClass::class)->willReturn(true);

        $composite = new AutomaticRedirectUrlResolver();
        $composite->add($first->reveal());
        $composite->add($second->reveal());

        self::assertTrue($composite->supports(stdClass::class));
    }

    public function test_supports_returns_false_when_no_child_supports_the_class(): void
    {
        $first = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $first->supports(stdClass::class)->willReturn(false);

        $composite = new AutomaticRedirectUrlResolver();
        $composite->add($first->reveal());

        self::assertFalse($composite->supports(stdClass::class));
    }

    public function test_resolve_throws_when_no_child_supports_the_class(): void
    {
        $first = $this->prophesize(AutomaticRedirectUrlResolverInterface::class);
        $first->supports(stdClass::class)->willReturn(false);

        $composite = new AutomaticRedirectUrlResolver();
        $composite->add($first->reveal());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(stdClass::class);

        $composite->resolve(new stdClass(), 'new-slug', 'en_US');
    }

    public function test_resolve_throws_when_composite_has_no_children(): void
    {
        $composite = new AutomaticRedirectUrlResolver();

        $this->expectException(RuntimeException::class);

        $composite->resolve(new stdClass(), 'new-slug', 'en_US');
    }
}
