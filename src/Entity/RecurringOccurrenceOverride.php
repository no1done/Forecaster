<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A one-off change to a single occurrence of a recurring transaction: a
 * different amount, a different date, or both.
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['recurring_transaction_id', 'scheduled_date'])]
class RecurringOccurrenceOverride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Null keeps the usual amount; zero skips the occurrence. */
    #[ORM\Column(nullable: true)]
    private ?int $amountPence = null;

    /** Null keeps the date the rule gives, after working-day adjustment. */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $date = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'occurrenceOverrides')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private RecurringTransaction $recurringTransaction,

        /** The date the rule says it is due, before any working-day adjustment. */
        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private \DateTimeImmutable $scheduledDate,
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

    public function getAmountPence(): ?int
    {
        return $this->amountPence;
    }

    public function setAmountPence(?int $amountPence): void
    {
        $this->amountPence = $amountPence;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?\DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function isEmpty(): bool
    {
        return null === $this->amountPence && null === $this->date;
    }
}
