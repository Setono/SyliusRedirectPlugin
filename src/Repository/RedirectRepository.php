<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Repository;

use DateInterval;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;

class RedirectRepository extends EntityRepository implements RedirectRepositoryInterface
{
    /**
     * @throws Exception
     */
    public function removeNotAccessed(int $threshold): void
    {
        if ($threshold <= 0) {
            return;
        }

        $dateTimeThreshold = (new DateTime())->sub(new DateInterval('P' . $threshold . 'D'));

        $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.lastAccessed is not null')
            ->andWhere('r.lastAccessed <= :threshold')
            ->setParameter('threshold', $dateTimeThreshold)
            ->getQuery()
            ->execute()
        ;
    }

    public function findEnabledBySourceAndChannel(string $source, ChannelInterface $channel, bool $only404 = false): ?RedirectInterface
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o, c')
            ->andWhere('o.source = :source')
            ->andWhere('o.enabled = true')
            ->setParameter('source', $source)
            ->leftJoin('o.channels', 'c')
        ;

        if ($only404) {
            $qb->andWhere('o.only404 = true');
        }

        /** @var RedirectInterface[] $redirects */
        $redirects = $qb->getQuery()->getResult();

        $preferredRedirect = null;

        foreach ($redirects as $redirect) {
            if (null === $preferredRedirect) {
                $preferredRedirect = $redirect;

                continue;
            }

            if ($redirect->hasChannel($channel)) {
                $preferredRedirect = $redirect;

                break;
            }
        }

        return $preferredRedirect;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findEnabledBySource(string $source, bool $only404 = false, bool $fetchJoinChannels = false): ?RedirectInterface
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.source = :source')
            ->andWhere('r.enabled = true')
            ->setMaxResults(1)
            ->setParameter('source', $source);

        if ($only404) {
            $qb->andWhere('r.only404 = true');
        }

        if ($fetchJoinChannels) {
            $qb->select('r, c')
                ->leftJoin('r.channels', 'c')
            ;
        }

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    public function searchNextRedirectByChannel(RedirectInterface $redirect, ChannelInterface $channel, bool $only404 = false): ?RedirectInterface
    {
        $nextRedirection = $this->findEnabledBySourceAndChannel($redirect->getDestination(), $channel, $only404);

        return $nextRedirection;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function searchNextRedirect(RedirectInterface $redirect, bool $only404 = false): ?RedirectInterface
    {
        $nextRedirection = $this->findEnabledBySource($redirect->getDestination(), $only404);

        return $nextRedirection;
    }

    public function findLastRedirectByChannel(RedirectInterface $redirect, ChannelInterface $channel, bool $only404 = false): RedirectInterface
    {
        do {
            $nextRedirect = $this->searchNextRedirectByChannel($redirect, $channel, $only404);
        } while ($nextRedirect instanceof RedirectInterface && ($redirect = $nextRedirect) !== null);

        return $nextRedirect ?? $redirect;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findLastRedirect(RedirectInterface $redirect, bool $only404 = false): RedirectInterface
    {
        do {
            $nextRedirect = $this->searchNextRedirect($redirect, $only404);
        } while ($nextRedirect instanceof RedirectInterface && ($redirect = $nextRedirect) !== null);

        return $nextRedirect ?? $redirect;
    }
}
