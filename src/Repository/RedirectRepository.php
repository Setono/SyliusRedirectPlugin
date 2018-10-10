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

    /**
     * @param int $threshold
     *
     * @throws \Exception
     */
    public function removeNotAccessed(int $threshold): void
    {
        if ($threshold <= 0) {
            return;
        }

        $dateTimeThreshold = (new \DateTime())->sub(new \DateInterval('P' . $threshold . 'D'));

        $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.lastAccessed is not null')
            ->andWhere('r.lastAccessed <= :threshold')
            ->setParameter('threshold', $dateTimeThreshold)
            ->getQuery()
            ->execute()
        ;
    }
}
