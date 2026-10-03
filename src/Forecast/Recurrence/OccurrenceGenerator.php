<?php

namespace App\Forecast\Recurrence;

use App\Forecast\Calendar\WorkingDayCalendar;

/**
 * Expands a rule into actual payment dates, moving any that fall on a
 * weekend or bank holiday back to the previous working day.
 */
final class OccurrenceGenerator
{
    /** Furthest a date can be moved back, e.g. over Christmas plus a weekend. */
    private const string MAX_SHIFT = '+7 days';

    public function __construct(
        private readonly WorkingDayCalendar $calendar,
    ) {
    }

    /**
     * @return list<Occurrence> those whose adjusted date is within $from..$to inclusive
     */
    public function between(RecurrenceRule $rule, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $lastNominal = $to->modify(self::MAX_SHIFT);
        if (null !== $rule->endDate && $rule->endDate < $lastNominal) {
            $lastNominal = $rule->endDate;
        }

        $occurrences = [];
        for ($n = 0; ($nominal = $rule->nominalDate($n)) <= $lastNominal; ++$n) {
            $date = $this->calendar->onOrBefore($nominal);
            if ($date >= $from && $date <= $to) {
                $occurrences[] = new Occurrence($nominal, $date);
            }
        }

        return $occurrences;
    }
}
