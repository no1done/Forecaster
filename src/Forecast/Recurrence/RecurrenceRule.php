<?php

namespace App\Forecast\Recurrence;

/**
 * When a recurring item is due, before any working-day adjustment.
 *
 * Monthly and yearly rules repeat on the start date's day, clamped to the
 * end of shorter months. Interval 2 on a weekly rule means fortnightly.
 */
final readonly class RecurrenceRule
{
    public function __construct(
        public Frequency $frequency,
        public \DateTimeImmutable $startDate,
        public int $interval = 1,
        public ?\DateTimeImmutable $endDate = null,
    ) {
        if ($interval < 1) {
            throw new \InvalidArgumentException('Interval must be at least 1.');
        }
        if (null !== $endDate && $endDate < $startDate) {
            throw new \InvalidArgumentException('End date cannot be before the start date.');
        }
    }

    /**
     * The nth due date, counting from 0 at the start date.
     */
    public function nominalDate(int $n): \DateTimeImmutable
    {
        $start = $this->startDate;
        $step = $n * $this->interval;

        return match ($this->frequency) {
            Frequency::Weekly => $start->modify(\sprintf('+%d days', 7 * $step)),
            Frequency::Monthly => self::clampedDate((int) $start->format('Y'), (int) $start->format('n') + $step, (int) $start->format('j')),
            Frequency::Yearly => self::clampedDate((int) $start->format('Y') + $step, (int) $start->format('n'), (int) $start->format('j')),
            Frequency::LastWorkingDayOfMonth => self::clampedDate((int) $start->format('Y'), (int) $start->format('n') + $step, 31),
        };
    }

    public function isScheduledOn(\DateTimeImmutable $date): bool
    {
        if ($date < $this->startDate || (null !== $this->endDate && $date > $this->endDate)) {
            return false;
        }

        for ($n = 0; ; ++$n) {
            $nominal = $this->nominalDate($n);
            if ($nominal >= $date) {
                return $nominal->format('Y-m-d') === $date->format('Y-m-d');
            }
        }
    }

    private static function clampedDate(int $year, int $month, int $day): \DateTimeImmutable
    {
        $firstOfMonth = (new \DateTimeImmutable())->setDate($year, $month, 1)->setTime(0, 0);

        return $firstOfMonth->setDate(
            (int) $firstOfMonth->format('Y'),
            (int) $firstOfMonth->format('n'),
            min($day, (int) $firstOfMonth->format('t')),
        );
    }
}
