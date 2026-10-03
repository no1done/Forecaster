<?php

namespace App\Forecast;

final class ForecastCalculator
{
    /**
     * Running end-of-day balance for every day from $from to $to inclusive,
     * starting from the latest checkpoint on or before $from.
     *
     * @param list<Checkpoint> $checkpoints
     * @param list<Movement>   $movements
     *
     * @return list<ForecastDay>
     */
    public function calculate(array $checkpoints, array $movements, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $checkpointsByDay = [];
        foreach ($checkpoints as $checkpoint) {
            $checkpointsByDay[$checkpoint->date->format('Y-m-d')] = $checkpoint;
        }
        ksort($checkpointsByDay);

        $anchor = null;
        foreach ($checkpointsByDay as $checkpoint) {
            if ($checkpoint->date > $from) {
                break;
            }
            $anchor = $checkpoint;
        }
        if (null === $anchor) {
            throw NoStartingBalance::before($from);
        }

        $movementsByDay = [];
        foreach ($movements as $movement) {
            $movementsByDay[$movement->date->format('Y-m-d')][] = $movement;
        }

        $days = [];
        $balance = 0;
        for ($date = $anchor->date; $date <= $to; $date = $date->modify('+1 day')) {
            $key = $date->format('Y-m-d');
            $dayMovements = $movementsByDay[$key] ?? [];
            $checkpoint = $checkpointsByDay[$key] ?? null;

            $balance = $checkpoint->balancePence
                ?? $balance + array_sum(array_map(static fn (Movement $m) => $m->amountPence, $dayMovements));

            if ($date >= $from) {
                $days[] = new ForecastDay($date, $balance, $dayMovements, null !== $checkpoint);
            }
        }

        return $days;
    }
}
