<?php

namespace App\Forecast;

/**
 * The real account balance at the end of a day.
 */
final readonly class Checkpoint
{
    public function __construct(
        public \DateTimeImmutable $date,
        public int $balancePence,
    ) {
    }
}
