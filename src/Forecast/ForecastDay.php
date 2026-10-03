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

    /**
     * @return list<Movement> the movements to list, leaving out hidden ones
     */
    public function visibleMovements(): array
    {
        return array_values(array_filter($this->movements, static fn (Movement $m) => !$m->hidden));
    }
}
