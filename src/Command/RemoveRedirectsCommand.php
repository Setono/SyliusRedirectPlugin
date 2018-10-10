<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Command;

use Setono\SyliusRedirectPlugin\Repository\RedirectRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveRedirectsCommand extends Command
{
    /**
     * @var RedirectRepository
     */
    private $redirectRepository;

    /**
     * @var int
     */
    private $removeAfter;

    /**
     * @param RedirectRepository $redirectRepository
     * @param int $removeAfter The number of days that has to go before removing redirects
     */
    public function __construct(RedirectRepository $redirectRepository, int $removeAfter)
    {
        parent::__construct();

        $this->redirectRepository = $redirectRepository;
        $this->removeAfter = $removeAfter;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('setono:sylius-redirect:remove')
            ->setDescription('This command will remove redirects that have not been accessed later than x days ago where x is the `setono_sylius_redirect.remove_after` parameter')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->redirectRepository->removeNotAccessed($this->removeAfter);
    }
}
