<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Repository;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class RedirectRepository extends EntityRepository implements RedirectRepositoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findEnabledBySource(string $source, bool $only404 = false): ?RedirectInterface
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.source = :source')
            ->andWhere('r.enabled = true')
            ->setParameter('source', $source);

        if ($only404) {
            $qb->andWhere('r.only404 = true');
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
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function searchNextRedirect(RedirectInterface $redirect, bool $only404 = false): ?RedirectInterface
    {
        $nextRedirection = $this->findEnabledBySource($redirect->getDestination(), $only404);

        return $nextRedirection;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastRedirect(RedirectInterface $redirect, bool $only404 = false): RedirectInterface
    {
        do {
            $nextRedirect = $this->searchNextRedirect($redirect, $only404);
        } while ($nextRedirect instanceof RedirectInterface && ($redirect = $nextRedirect) !== null);

        return $nextRedirect ?? $redirect;
    }
}
