<?php

namespace Setono\SyliusRedirectPlugin\Repository;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface RedirectRepositoryInterface extends RepositoryInterface
{
    /**
     * @param string $source
     *
     * @return RedirectInterface|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findBySource(string $source): ?RedirectInterface;
    
    /**
     * @param string $source
     * @param bool   $onlyNotFound
     *
     * @return RedirectInterface|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
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
     *
     * @return null|RedirectInterface
     */
    public function searchNextRedirection(RedirectInterface $redirection): ?RedirectInterface;
}