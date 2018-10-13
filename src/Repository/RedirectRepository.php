<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Repository;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class RedirectRepository extends EntityRepository implements RedirectRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findBySource(string $source): ?RedirectInterface
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.source = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findEnabledBySource(string $source, bool $onlyNotFound = false): ?RedirectInterface
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.source = :source')
            ->andWhere('r.enabled = 1')
            ->setParameter('source', $source);
        
        if ($onlyNotFound) {
            $qb->andWhere('r.redirectFound = 0');
        }
        
        return $qb->getQuery()
            ->getOneOrNullResult();
    }
    
    /**
     * {@inheritdoc}
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
    
    /**
     * {@inheritdoc}
     */
    public function searchNextRedirection(RedirectInterface $redirection): ?RedirectInterface
    {
        $nextRedirection = $this->findOneBy(['source' => $redirection->getDestination(), 'enabled' => true]);
        
        return $nextRedirection;
    }
}
