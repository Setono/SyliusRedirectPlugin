<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Command;

use Setono\SyliusRedirectPlugin\Pruner\PrunerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'setono:sylius-redirect:prune',
    description: 'Removes redirects that have not been accessed in the last `setono_sylius_redirect.remove_after` days',
)]
final class PruneCommand extends Command
{
    /** @param int $removeAfter Number of days that has to pass before a redirect is removed */
    public function __construct(
        private readonly PrunerInterface $pruner,
        private readonly int $removeAfter,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->pruner->prune($this->removeAfter);

        $output->writeln(sprintf('Pruned %d redirect(s).', $count));

        return Command::SUCCESS;
    }
}
