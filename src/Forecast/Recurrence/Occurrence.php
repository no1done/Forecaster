<?php

namespace App\Forecast\Recurrence;

final readonly class Occurrence
{
    /**
     * @param \DateTimeImmutable $scheduledDate when the rule says it is due; identifies the occurrence
     * @param \DateTimeImmutable $date          when it actually leaves or arrives, after working-day adjustment
     */
    public function __construct(
        public \DateTimeImmutable $scheduledDate,
        public \DateTimeImmutable $date,
    ) {
    }
}
