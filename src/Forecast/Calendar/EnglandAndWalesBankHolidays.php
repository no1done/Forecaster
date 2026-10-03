<?php

namespace App\Forecast\Calendar;

/**
 * Regular England & Wales bank holidays, calculated for any year.
 *
 * One-off holidays (coronations, jubilees, moved VE days) are not included.
 */
final class EnglandAndWalesBankHolidays implements BankHolidays
{
    /** @var array<int, array<string, true>> */
    private array $cache = [];

    public function isBankHoliday(\DateTimeImmutable $date): bool
    {
        $year = (int) $date->format('Y');
        $this->cache[$year] ??= array_fill_keys(
            array_map(static fn (\DateTimeImmutable $d) => $d->format('Y-m-d'), $this->forYear($year)),
            true,
        );

        return isset($this->cache[$year][$date->format('Y-m-d')]);
    }

    /**
     * @return list<\DateTimeImmutable> sorted by date
     */
    public function forYear(int $year): array
    {
        $easterSunday = $this->easterSunday($year);

        $holidays = $this->withSubstituteDays([
            self::date($year, 1, 1),
            self::date($year, 12, 25),
            self::date($year, 12, 26),
        ]);
        $holidays[] = $easterSunday->modify('-2 days');
        $holidays[] = $easterSunday->modify('+1 day');
        $holidays[] = new \DateTimeImmutable("first monday of may $year");
        $holidays[] = new \DateTimeImmutable("last monday of may $year");
        $holidays[] = new \DateTimeImmutable("last monday of august $year");

        usort($holidays, static fn (\DateTimeImmutable $a, \DateTimeImmutable $b) => $a <=> $b);

        return $holidays;
    }

    /**
     * A fixed-date holiday that lands on a weekend (or on another holiday's
     * substitute day) moves to the next free weekday.
     *
     * @param list<\DateTimeImmutable> $dates
     *
     * @return list<\DateTimeImmutable>
     */
    private function withSubstituteDays(array $dates): array
    {
        $taken = [];
        foreach ($dates as $date) {
            while (self::isWeekend($date) || isset($taken[$date->format('Y-m-d')])) {
                $date = $date->modify('+1 day');
            }
            $taken[$date->format('Y-m-d')] = $date;
        }

        return array_values($taken);
    }

    /**
     * Anonymous Gregorian algorithm (Meeus/Jones/Butcher).
     */
    private function easterSunday(int $year): \DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return self::date($year, $month, $day);
    }

    private static function date(int $year, int $month, int $day): \DateTimeImmutable
    {
        return new \DateTimeImmutable(\sprintf('%04d-%02d-%02d', $year, $month, $day));
    }

    private static function isWeekend(\DateTimeImmutable $date): bool
    {
        return (int) $date->format('N') >= 6;
    }
}
