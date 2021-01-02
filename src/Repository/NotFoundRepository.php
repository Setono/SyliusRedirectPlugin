<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Repository;

use Setono\SyliusRedirectPlugin\Model\NotFoundInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class NotFoundRepository extends EntityRepository implements NotFoundRepositoryInterface
{
    public function findOneByUrl(string $url): ?NotFoundInterface
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.url = :url')
            ->setParameter('url', $url)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
