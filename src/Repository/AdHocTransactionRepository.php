<?php

namespace App\Repository;

use App\Entity\AdHocTransaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdHocTransaction>
 */
class AdHocTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdHocTransaction::class);
    }

    /**
     * @return list<AdHocTransaction>
     */
    public function findBetween(User $owner, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.owner = :owner')
            ->andWhere('t.date BETWEEN :from AND :to')
            ->setParameter('owner', $owner)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('t.date')
            ->addOrderBy('t.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<AdHocTransaction>
     */
    public function findOnOrAfter(User $owner, \DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.owner = :owner')
            ->andWhere('t.date >= :date')
            ->setParameter('owner', $owner)
            ->setParameter('date', $date)
            ->orderBy('t.date')
            ->addOrderBy('t.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<AdHocTransaction> newest first
     */
    public function findRecentBefore(User $owner, \DateTimeImmutable $date, int $limit): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.owner = :owner')
            ->andWhere('t.date < :date')
            ->setParameter('owner', $owner)
            ->setParameter('date', $date)
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
