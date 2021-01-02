<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Repository;

use Setono\SyliusRedirectPlugin\Model\NotFoundInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface NotFoundRepositoryInterface extends RepositoryInterface
{
    public function findOneByUrl(string $url): ?NotFoundInterface;
}
