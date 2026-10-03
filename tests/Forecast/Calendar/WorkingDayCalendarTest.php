<?php

namespace App\Tests\Forecast\Calendar;

use App\Forecast\Calendar\EnglandAndWalesBankHolidays;
use App\Forecast\Calendar\WorkingDayCalendar;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WorkingDayCalendarTest extends TestCase
{
    #[Test]
    #[DataProvider('dates')]
    public function itFindsTheWorkingDayOnOrBeforeADate(string $date, string $expected): void
    {
        $calendar = new WorkingDayCalendar(new EnglandAndWalesBankHolidays());

        self::assertSame($expected, $calendar->onOrBefore(new \DateTimeImmutable($date))->format('Y-m-d'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function dates(): iterable
    {
        yield 'a normal weekday stays put' => ['2026-06-10', '2026-06-10'];
        yield 'Saturday moves to Friday' => ['2026-06-13', '2026-06-12'];
        yield 'Sunday moves to Friday' => ['2026-06-14', '2026-06-12'];
        yield 'bank holiday Monday moves to Friday' => ['2026-08-31', '2026-08-28'];
        yield 'Easter Monday skips Good Friday too' => ['2026-04-06', '2026-04-02'];
        yield 'Christmas substitute day skips the whole cluster' => ['2026-12-28', '2026-12-24'];
    }

    #[Test]
    public function itKnowsWorkingDays(): void
    {
        $calendar = new WorkingDayCalendar(new EnglandAndWalesBankHolidays());

        self::assertTrue($calendar->isWorkingDay(new \DateTimeImmutable('2026-06-10')));
        self::assertFalse($calendar->isWorkingDay(new \DateTimeImmutable('2026-06-13')));
        self::assertFalse($calendar->isWorkingDay(new \DateTimeImmutable('2026-08-31')));
    }
}
