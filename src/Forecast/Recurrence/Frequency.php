<?php

namespace App\Forecast\Recurrence;

enum Frequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case LastWorkingDayOfMonth = 'last_working_day_of_month';
}
