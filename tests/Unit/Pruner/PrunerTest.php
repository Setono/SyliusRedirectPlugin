<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\Pruner;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusRedirectPlugin\Model\Redirect;
use Setono\SyliusRedirectPlugin\Pruner\Pruner;

final class PrunerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * The threshold-zero contract: prune(0) is a no-op and never touches the manager registry.
     * The non-zero branch hits a Doctrine QueryBuilder + SimpleBatchIteratorAggregate, which
     * needs a real EntityManager — covered by functional tests, not here.
     */
    public function test_it_returns_zero_without_touching_the_registry_when_the_threshold_is_disabled(): void
    {
        $registry = $this->prophesize(ManagerRegistry::class);
        $registry->getManager()->shouldNotBeCalled();
        $registry->getManagerForClass(\Prophecy\Argument::any())->shouldNotBeCalled();

        $pruner = new Pruner($registry->reveal(), Redirect::class);

        self::assertSame(0, $pruner->prune(0));
        self::assertSame(0, $pruner->prune(-5));
    }
}
