<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Repository;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface RedirectRepositoryInterface extends RepositoryInterface
{
    /**
     * @param string $source
     * @param bool   $onlyNotFound
     *
     * @return RedirectInterface|null
     */
    public function findEnabledBySource(string $source, bool $onlyNotFound = false): ?RedirectInterface;

    /**
     * @param int $threshold
     *
     * @throws \Exception
     */
    public function removeNotAccessed(int $threshold): void;

    /**
     * @param RedirectInterface $redirection
     * @param bool              $onlyNotFound
     *
     * @return RedirectInterface|null
     */
    public function searchNextRedirect(RedirectInterface $redirection, bool $onlyNotFound = false): ?RedirectInterface;

    /**
     * @param RedirectInterface $redirect
     * @param bool              $onlyNotFound
     *
     * @return RedirectInterface
     */
    public function findLastRedirect(RedirectInterface $redirect, bool $onlyNotFound = false): RedirectInterface;
}
