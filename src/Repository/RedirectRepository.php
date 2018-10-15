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
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function searchNextRedirect(RedirectInterface $redirect, bool $onlyNotFound = false): ?RedirectInterface
    {
        $nextRedirection = $this->findEnabledBySource($redirect->getDestination(), $onlyNotFound);

        return $nextRedirection;
    }

    /**
     * @param RedirectInterface $redirect
     * @param bool              $onlyNotFound
     *
     * @return RedirectInterface
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastRedirect(RedirectInterface $redirect, bool $onlyNotFound = false): RedirectInterface
    {
        do {
            $nextRedirect = $this->searchNextRedirect($redirect, $onlyNotFound);
        } while ($nextRedirect instanceof RedirectInterface && $redirect = $nextRedirect);

        if ($nextRedirect instanceof RedirectInterface) {
            $lastRedirect = $nextRedirect;
        } else {
            $lastRedirect = $redirect;
        }

        return $lastRedirect;
    }
}
