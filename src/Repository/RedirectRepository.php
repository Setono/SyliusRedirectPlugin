<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Repository;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;

class RedirectRepository extends EntityRepository implements RedirectRepositoryInterface
{
    public function findOneEnabledBySource(string $source, ?ChannelInterface $channel = null, ?bool $only404 = null): ?RedirectInterface
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.source = :source')
            ->andWhere('o.enabled = true')
            ->setParameter('source', $source)
        ;

        if (null !== $channel) {
            $qb
                ->select('o, c')
                ->leftJoin('o.channels', 'c')
            ;
        }

        if (null !== $only404) {
            $qb
                ->andWhere('o.only404 = :only404')
                ->setParameter('only404', $only404)
            ;
        }

        /** @var RedirectInterface[] $redirects */
        $redirects = $qb->getQuery()->getResult();

        if (count($redirects) === 0) {
            return null;
        }

        $preferredRedirect = null;

        foreach ($redirects as $redirect) {
            if (null === $preferredRedirect && $redirect->getChannels()->count() === 0) {
                $preferredRedirect = $redirect;
            }

            if (null !== $channel && $redirect->hasChannel($channel)) {
                $preferredRedirect = $redirect;

                break;
            }
        }

        return $preferredRedirect;
    }

    public function findOneBySource(string $source): ?RedirectInterface
    {
        $redirect = $this->findOneBy(['source' => $source]);

        return $redirect instanceof RedirectInterface ? $redirect : null;
    }
}
