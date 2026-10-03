<?php

namespace App\Forecast;

final readonly class ForecastDay
{
    /**
     * @param int            $balancePence balance at the end of the day
     * @param list<Movement> $movements
     */
    public function __construct(
        public \DateTimeImmutable $date,
        public int $balancePence,
        public array $movements,
        public bool $isCheckpoint,
    ) {
    }
}
