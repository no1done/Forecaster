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
    ) {
    }
}
