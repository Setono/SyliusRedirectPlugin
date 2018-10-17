<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Repository;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface RedirectRepositoryInterface extends RepositoryInterface
{
    /**
     * @param string $source
     * @param bool   $only404
     *
     * @return RedirectInterface|null
     */
    public function findEnabledBySource(string $source, bool $only404 = false): ?RedirectInterface;

    /**
     * @param int $threshold
     *
     * @throws \Exception
     */
    public function removeNotAccessed(int $threshold): void;

    /**
     * @param RedirectInterface $redirection
     * @param bool              $only404
     *
     * @return RedirectInterface|null
     */
    public function searchNextRedirect(RedirectInterface $redirection, bool $only404 = false): ?RedirectInterface;

    /**
     * @param RedirectInterface $redirect
     * @param bool              $only404
     *
     * @return RedirectInterface
     */
    public function findLastRedirect(RedirectInterface $redirect, bool $only404 = false): RedirectInterface;
}
