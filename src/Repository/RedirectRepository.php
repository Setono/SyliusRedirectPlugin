<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Repository;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class RedirectRepository extends EntityRepository
{
    /**
     * @param string $sourceHash
     *
     * @return RedirectInterface|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findBySourceHash(string $sourceHash): ?RedirectInterface
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.sourceHash = :sourceHash')
            ->setParameter('sourceHash', $sourceHash)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
