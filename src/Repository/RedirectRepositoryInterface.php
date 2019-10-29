<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Repository;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface RedirectRepositoryInterface extends RepositoryInterface
{
    public function findEnabledBySource(string $source, bool $only404 = false): ?RedirectInterface;

    public function removeNotAccessed(int $threshold): void;

    public function searchNextRedirect(RedirectInterface $redirection, bool $only404 = false): ?RedirectInterface;

    public function findLastRedirect(RedirectInterface $redirect, bool $only404 = false): RedirectInterface;
}
