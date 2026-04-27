<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusRedirectPlugin\Command\PruneCommand;
use Setono\SyliusRedirectPlugin\Pruner\PrunerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class PruneCommandTest extends TestCase
{
    use ProphecyTrait;

    public function test_it_prunes_redirects_using_the_configured_threshold_and_reports_the_count(): void
    {
        $pruner = $this->prophesize(PrunerInterface::class);
        $pruner->prune(30)->shouldBeCalledOnce()->willReturn(7);

        $tester = $this->createTesterFor(new PruneCommand($pruner->reveal(), 30));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Pruned 7 redirect(s).', $tester->getDisplay());
    }

    public function test_it_reports_zero_when_nothing_is_eligible(): void
    {
        $pruner = $this->prophesize(PrunerInterface::class);
        $pruner->prune(30)->willReturn(0);

        $tester = $this->createTesterFor(new PruneCommand($pruner->reveal(), 30));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Pruned 0 redirect(s).', $tester->getDisplay());
    }

    private function createTesterFor(PruneCommand $command): CommandTester
    {
        $application = new Application();
        $application->add($command);

        return new CommandTester($application->find('setono:sylius-redirect:prune'));
    }
}
