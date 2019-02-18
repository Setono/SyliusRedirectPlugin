<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Behat\Context\Transform;

use Behat\Behat\Context\Context;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Webmozart\Assert\Assert;

final class RedirectContext implements Context
{
    /**
     * @var RepositoryInterface
     */
    private $redirectRepository;

    public function __construct(RepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @Transform :redirect
     */
    public function getRedirectBySource($redirect)
    {
        $redirects = $this->redirectRepository->findBy([
            'source' => $redirect,
        ]);

        Assert::eq(
            count($redirects),
            1,
            sprintf('%d redirects has been found with source "%s".', count($redirects), $redirect)
        );

        return $redirects[0];
    }
}
