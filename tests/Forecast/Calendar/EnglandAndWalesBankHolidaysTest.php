<?php

namespace App\Tests\Forecast\Calendar;

use App\Forecast\Calendar\EnglandAndWalesBankHolidays;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnglandAndWalesBankHolidaysTest extends TestCase
{
    /**
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('years')]
    public function itListsTheBankHolidaysForAYear(int $year, array $expected): void
    {
        $holidays = (new EnglandAndWalesBankHolidays())->forYear($year);

        self::assertSame($expected, array_map(static fn (\DateTimeImmutable $d) => $d->format('Y-m-d'), $holidays));
    }

    /**
     * @return iterable<string, array{int, list<string>}>
     */
    public static function years(): iterable
    {
        yield '2025: nothing falls on a weekend' => [2025, [
            '2025-01-01', '2025-04-18', '2025-04-21', '2025-05-05',
            '2025-05-26', '2025-08-25', '2025-12-25', '2025-12-26',
        ]];

        yield '2026: Boxing Day on a Saturday moves to Monday' => [2026, [
            '2026-01-01', '2026-04-03', '2026-04-06', '2026-05-04',
            '2026-05-25', '2026-08-31', '2026-12-25', '2026-12-28',
        ]];

        yield '2027: Christmas on Saturday and Boxing Day on Sunday move to Mon and Tue' => [2027, [
            '2027-01-01', '2027-03-26', '2027-03-29', '2027-05-03',
            '2027-05-31', '2027-08-30', '2027-12-27', '2027-12-28',
        ]];

        yield '2028: New Year on a Saturday moves to Monday' => [2028, [
            '2028-01-03', '2028-04-14', '2028-04-17', '2028-05-01',
            '2028-05-29', '2028-08-28', '2028-12-25', '2028-12-26',
        ]];
    }

    #[Test]
    public function itKnowsWhetherADateIsABankHoliday(): void
    {
        $holidays = new EnglandAndWalesBankHolidays();

        self::assertTrue($holidays->isBankHoliday(new \DateTimeImmutable('2026-12-28')));
        self::assertFalse($holidays->isBankHoliday(new \DateTimeImmutable('2026-12-26')));
        self::assertFalse($holidays->isBankHoliday(new \DateTimeImmutable('2026-06-10')));
    }
}
