<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A one-off amount for a single occurrence of a recurring transaction.
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['recurring_transaction_id', 'scheduled_date'])]
class RecurringAmountOverride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'amountOverrides')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private RecurringTransaction $recurringTransaction,

        /** The date the rule says it is due, before any working-day adjustment. */
        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private \DateTimeImmutable $scheduledDate,

        /** Zero skips the occurrence. */
        #[ORM\Column]
        private int $amountPence,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecurringTransaction(): RecurringTransaction
    {
        return $this->recurringTransaction;
    }

    public function getScheduledDate(): \DateTimeImmutable
    {
        return $this->scheduledDate;
    }

    public function getAmountPence(): int
    {
        return $this->amountPence;
    }

    public function setAmountPence(int $amountPence): void
    {
        $this->amountPence = $amountPence;
    }
}
