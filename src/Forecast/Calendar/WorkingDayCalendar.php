<?php

namespace App\Forecast\Calendar;

final class WorkingDayCalendar
{
    public function __construct(
        private readonly BankHolidays $bankHolidays,
    ) {
    }

    public function isWorkingDay(\DateTimeImmutable $date): bool
    {
        return (int) $date->format('N') < 6 && !$this->bankHolidays->isBankHoliday($date);
    }

    public function onOrBefore(\DateTimeImmutable $date): \DateTimeImmutable
    {
        while (!$this->isWorkingDay($date)) {
            $date = $date->modify('-1 day');
        }

        return $date;
    }
}
