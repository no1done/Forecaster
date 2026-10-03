<?php

namespace App\Repository;

use App\Entity\RecurringTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecurringTransaction>
 */
class RecurringTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringTransaction::class);
    }

    /**
     * Rules that have not ended before $from. Start dates are left to the
     * occurrence generator, as a weekend start can shift back into range.
     *
     * @return list<RecurringTransaction>
     */
    public function findNotEndedBefore(\DateTimeImmutable $from): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.occurrenceOverrides', 'o')
            ->addSelect('o')
            ->where('r.endDate IS NULL OR r.endDate >= :from')
            ->setParameter('from', $from)
            ->orderBy('r.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<RecurringTransaction>
     */
    public function findAllByDescription(): array
    {
        return $this->findBy([], ['description' => 'ASC']);
    }
}
