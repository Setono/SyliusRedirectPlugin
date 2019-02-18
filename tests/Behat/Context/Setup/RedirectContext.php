<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class RedirectContext implements Context
{
    /**
     * @var RepositoryInterface
     */
    private $redirectRepository;

    /**
     * @var FactoryInterface
     */
    private $redirectFactory;

    public function __construct(RepositoryInterface $brandRepository, FactoryInterface $brandFactory)
    {
        $this->redirectRepository = $brandRepository;
        $this->redirectFactory = $brandFactory;
    }

    /**
     * @Given the store has a redirect from path :oldPath to :newPath
     */
    public function storeHasARedirect($oldPath, $newPath): void
    {
        $redirect = $this->createRedirect($oldPath, $newPath);

        $this->saveRedirect($redirect);
    }

    private function createRedirect(string $oldPath, string $newPath): RedirectInterface
    {
        /** @var RedirectInterface $redirect */
        $redirect = $this->redirectFactory->createNew();

        $redirect->setEnabled(true);
        $redirect->setSource($oldPath);
        $redirect->setDestination($newPath);
        $redirect->setPermanent(true);

        return $redirect;
    }

    private function saveRedirect(RedirectInterface $redirect): void
    {
        $this->redirectRepository->add($redirect);
    }
}
