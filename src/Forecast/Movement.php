<?php

namespace App\Forecast;

/**
 * Money in (positive) or out (negative) of the account on a given day.
 */
final readonly class Movement
{
    public function __construct(
        public \DateTimeImmutable $date,
        public int $amountPence,
        public string $description,
        public ?int $adHocTransactionId = null,
        public ?int $recurringTransactionId = null,
        /** For a recurring movement, the date the rule scheduled it, before working-day adjustment. */
        public ?\DateTimeImmutable $scheduledDate = null,
        /** For a recurring movement, its amount when there is no one-off change. */
        public ?int $usualAmountPence = null,
        /** Counts towards the balance but is not listed on the forecast. */
        public bool $hidden = false,
        /** For a recurring movement moved as a one-off, the date it would otherwise be paid. */
        public ?\DateTimeImmutable $usualDate = null,
    ) {
    }

    public function isOneOffAmount(): bool
    {
        return null !== $this->usualAmountPence && $this->usualAmountPence !== $this->amountPence;
    }

    public function isOneOffDate(): bool
    {
        return null !== $this->usualDate;
    }
}
