<?php

namespace App\Forecast\Calendar;

interface BankHolidays
{
    public function isBankHoliday(\DateTimeImmutable $date): bool;
}
