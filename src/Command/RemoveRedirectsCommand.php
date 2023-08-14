<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Command;

use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveRedirectsCommand extends Command
{
    protected static $defaultName = 'setono:sylius-redirect:remove';

    protected static $defaultDescription = 'This command will remove redirects that have not been accessed later than x days ago where x is the `setono_sylius_redirect.remove_after` parameter';

    private RedirectRepositoryInterface $redirectRepository;

    private int $removeAfter;

    /**
     * @param int $removeAfter The number of days that has to go before removing redirects
     */
    public function __construct(RedirectRepositoryInterface $redirectRepository, int $removeAfter)
    {
        parent::__construct();

        $this->redirectRepository = $redirectRepository;
        $this->removeAfter = $removeAfter;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->redirectRepository->removeNotAccessed($this->removeAfter);

        return 0;
    }
}
