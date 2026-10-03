<?php

namespace App\Repository;

use App\Entity\BalanceCheckpoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BalanceCheckpoint>
 */
class BalanceCheckpointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BalanceCheckpoint::class);
    }

    public function findLatestOnOrBefore(\DateTimeImmutable $date): ?BalanceCheckpoint
    {
        return $this->createQueryBuilder('c')
            ->where('c.date <= :date')
            ->setParameter('date', $date)
            ->orderBy('c.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<BalanceCheckpoint>
     */
    public function findBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.date BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('c.date')
            ->getQuery()
            ->getResult();
    }

    public function findOneOnDate(\DateTimeImmutable $date): ?BalanceCheckpoint
    {
        return $this->findOneBy(['date' => $date]);
    }

    /**
     * @return list<BalanceCheckpoint>
     */
    public function findNewestFirst(): array
    {
        return $this->findBy([], ['date' => 'DESC']);
    }
}
