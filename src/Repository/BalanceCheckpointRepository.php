<?php

namespace App\Repository;

use App\Entity\BalanceCheckpoint;
use App\Entity\User;
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

    public function findEarliest(User $owner): ?BalanceCheckpoint
    {
        return $this->findOneBy(['owner' => $owner], ['date' => 'ASC']);
    }

    public function findLatestOnOrBefore(User $owner, \DateTimeImmutable $date): ?BalanceCheckpoint
    {
        return $this->createQueryBuilder('c')
            ->where('c.owner = :owner')
            ->andWhere('c.date <= :date')
            ->setParameter('owner', $owner)
            ->setParameter('date', $date)
            ->orderBy('c.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<BalanceCheckpoint>
     */
    public function findBetween(User $owner, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.owner = :owner')
            ->andWhere('c.date BETWEEN :from AND :to')
            ->setParameter('owner', $owner)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('c.date')
            ->getQuery()
            ->getResult();
    }

    public function findOneOnDate(User $owner, \DateTimeImmutable $date): ?BalanceCheckpoint
    {
        return $this->findOneBy(['owner' => $owner, 'date' => $date]);
    }

    /**
     * @return list<BalanceCheckpoint>
     */
    public function findNewestFirst(User $owner): array
    {
        return $this->findBy(['owner' => $owner], ['date' => 'DESC']);
    }
}
