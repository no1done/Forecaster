<?php

namespace App\Repository;

use App\Entity\AdHocTransaction;
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
    public function findBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.date BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('t.date')
            ->addOrderBy('t.id')
            ->getQuery()
            ->getResult();
    }
}
