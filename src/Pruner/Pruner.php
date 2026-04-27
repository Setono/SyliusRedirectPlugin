<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Pruner;

use DateInterval;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;

final class Pruner implements PrunerInterface
{
    use ORMTrait;

    /**
     * @param class-string<RedirectInterface> $redirectClass
     * @param positive-int $batchSize
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly string $redirectClass,
        private readonly int $batchSize = 100,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function prune(int $thresholdInDays): int
    {
        if ($thresholdInDays <= 0) {
            return 0;
        }

        $threshold = (new DateTimeImmutable())->sub(new DateInterval('P' . $thresholdInDays . 'D'));
        $manager = $this->getManager($this->redirectClass);

        $query = $manager->createQueryBuilder()
            ->select('r')
            ->from($this->redirectClass, 'r')
            ->andWhere('(r.lastAccessed IS NOT NULL AND r.lastAccessed <= :threshold) OR (r.lastAccessed IS NULL AND r.createdAt <= :threshold)')
            ->setParameter('threshold', $threshold)
            ->getQuery()
        ;

        $count = 0;
        foreach (SimpleBatchIteratorAggregate::fromQuery($query, $this->batchSize) as $redirect) {
            /** @var RedirectInterface $redirect */
            $manager->remove($redirect);
            ++$count;
        }

        $manager->flush();

        return $count;
    }
}
