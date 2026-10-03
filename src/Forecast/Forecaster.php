<?php

namespace App\Forecast;

use App\Entity\AdHocTransaction;
use App\Entity\BalanceCheckpoint;
use App\Entity\RecurringTransaction;
use App\Forecast\Calendar\WorkingDayCalendar;
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
        private readonly WorkingDayCalendar $calendar,
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
            $moved = $recurring->getMovedDates();
            foreach ($this->occurrences->between($recurring->getRule(), $after, $to) as $occurrence) {
                if (!isset($moved[$occurrence->scheduledDate->format('Y-m-d')])) {
                    $movements[] = $this->movement($recurring, $occurrence->scheduledDate, $occurrence->date);
                }
            }
            // A one-off date can move an occurrence into or out of the range.
            foreach ($moved as $scheduled => $date) {
                $scheduledDate = new \DateTimeImmutable($scheduled);
                if ($date >= $after && $date <= $to && $recurring->getRule()->isScheduledOn($scheduledDate)) {
                    $movements[] = $this->movement($recurring, $scheduledDate, $date, usualDate: $this->calendar->onOrBefore($scheduledDate));
                }
            }
        }

        return $this->calculator->calculate(
            array_map(static fn (BalanceCheckpoint $c) => $c->toCheckpoint(), $this->checkpoints->findBetween($anchor->getDate(), $to)),
            $movements,
            $from,
            $to,
        );
    }

    private function movement(RecurringTransaction $recurring, \DateTimeImmutable $scheduledDate, \DateTimeImmutable $date, ?\DateTimeImmutable $usualDate = null): Movement
    {
        return new Movement(
            $date,
            $recurring->getAmountPenceFor($scheduledDate),
            $recurring->getDescription(),
            recurringTransactionId: $recurring->getId(),
            scheduledDate: $scheduledDate,
            usualAmountPence: $recurring->getAmountPence(),
            hidden: $recurring->isHiddenFromForecast(),
            usualDate: $usualDate,
        );
    }
}
