<?php

namespace App\Forecast;

use App\Entity\AdHocTransaction;
use App\Entity\BalanceCheckpoint;
use App\Forecast\Recurrence\OccurrenceGenerator;
use App\Repository\AdHocTransactionRepository;
use App\Repository\BalanceCheckpointRepository;
use App\Repository\RecurringTransactionRepository;

/**
 * Builds the daily forecast from everything stored in the database.
 */
final class Forecaster
{
    public function __construct(
        private readonly BalanceCheckpointRepository $checkpoints,
        private readonly RecurringTransactionRepository $recurringTransactions,
        private readonly AdHocTransactionRepository $adHocTransactions,
        private readonly OccurrenceGenerator $occurrences,
        private readonly ForecastCalculator $calculator,
    ) {
    }

    /**
     * @return list<ForecastDay>
     */
    public function forecast(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $anchor = $this->checkpoints->findLatestOnOrBefore($from) ?? throw NoStartingBalance::before($from);
        // Anything on the anchor's own day is already in its balance.
        $after = $anchor->getDate()->modify('+1 day');

        $movements = array_map(
            static fn (AdHocTransaction $t) => $t->toMovement(),
            $this->adHocTransactions->findBetween($after, $to),
        );
        foreach ($this->recurringTransactions->findNotEndedBefore($after) as $recurring) {
            foreach ($this->occurrences->between($recurring->getRule(), $after, $to) as $occurrence) {
                $movements[] = new Movement(
                    $occurrence->date,
                    $recurring->getAmountPenceFor($occurrence->scheduledDate),
                    $recurring->getDescription(),
                    recurringTransactionId: $recurring->getId(),
                    scheduledDate: $occurrence->scheduledDate,
                    usualAmountPence: $recurring->getAmountPence(),
                    hidden: $recurring->isHiddenFromForecast(),
                );
            }
        }

        return $this->calculator->calculate(
            array_map(static fn (BalanceCheckpoint $c) => $c->toCheckpoint(), $this->checkpoints->findBetween($anchor->getDate(), $to)),
            $movements,
            $from,
            $to,
        );
    }
}
