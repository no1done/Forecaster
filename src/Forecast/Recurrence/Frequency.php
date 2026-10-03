<?php

namespace App\Forecast\Recurrence;

enum Frequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case LastWorkingDayOfMonth = 'last_working_day_of_month';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Weekly',
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
            self::LastWorkingDayOfMonth => 'Last working day of the month',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::Weekly => 'weeks',
            self::Monthly, self::LastWorkingDayOfMonth => 'months',
            self::Yearly => 'years',
        };
    }
}
