<?php

namespace App\Tests\Forecast\Recurrence;

use App\Forecast\Calendar\EnglandAndWalesBankHolidays;
use App\Forecast\Calendar\WorkingDayCalendar;
use App\Forecast\Recurrence\Frequency;
use App\Forecast\Recurrence\OccurrenceGenerator;
use App\Forecast\Recurrence\RecurrenceRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OccurrenceGeneratorTest extends TestCase
{
    /**
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('rules')]
    public function itListsOccurrencesWithinAWindow(RecurrenceRule $rule, string $from, string $to, array $expected): void
    {
        $generator = new OccurrenceGenerator(new WorkingDayCalendar(new EnglandAndWalesBankHolidays()));

        $occurrences = $generator->between($rule, new \DateTimeImmutable($from), new \DateTimeImmutable($to));

        self::assertSame($expected, array_map(static fn (\DateTimeImmutable $d) => $d->format('Y-m-d'), $occurrences));
    }

    /**
     * @return iterable<string, array{RecurrenceRule, string, string, list<string>}>
     */
    public static function rules(): iterable
    {
        yield 'monthly, weekend dates move back to Friday' => [
            new RecurrenceRule(Frequency::Monthly, new \DateTimeImmutable('2026-01-15')),
            '2026-01-01', '2026-06-30',
            ['2026-01-15', '2026-02-13', '2026-03-13', '2026-04-15', '2026-05-15', '2026-06-15'],
        ];

        yield 'monthly rule that started long before the window' => [
            new RecurrenceRule(Frequency::Monthly, new \DateTimeImmutable('2020-01-15')),
            '2026-01-01', '2026-03-31',
            ['2026-01-15', '2026-02-13', '2026-03-13'],
        ];

        yield 'monthly on the 31st clamps to short months without drifting' => [
            new RecurrenceRule(Frequency::Monthly, new \DateTimeImmutable('2026-01-31')),
            '2026-01-01', '2026-04-30',
            ['2026-01-30', '2026-02-27', '2026-03-31', '2026-04-30'],
        ];

        yield 'every 3 months' => [
            new RecurrenceRule(Frequency::Monthly, new \DateTimeImmutable('2026-01-15'), interval: 3),
            '2026-01-01', '2026-12-31',
            ['2026-01-15', '2026-04-15', '2026-07-15', '2026-10-15'],
        ];

        yield 'last working day of month (salary)' => [
            new RecurrenceRule(Frequency::LastWorkingDayOfMonth, new \DateTimeImmutable('2026-01-01')),
            '2026-01-01', '2026-12-31',
            [
                '2026-01-30', '2026-02-27', '2026-03-31', '2026-04-30', '2026-05-29', '2026-06-30',
                '2026-07-31', '2026-08-28', '2026-09-30', '2026-10-30', '2026-11-30', '2026-12-31',
            ],
        ];

        yield 'fortnightly' => [
            new RecurrenceRule(Frequency::Weekly, new \DateTimeImmutable('2026-01-02'), interval: 2),
            '2026-01-01', '2026-02-28',
            ['2026-01-02', '2026-01-16', '2026-01-30', '2026-02-13', '2026-02-27'],
        ];

        yield 'fortnightly landing on a bank holiday' => [
            new RecurrenceRule(Frequency::Weekly, new \DateTimeImmutable('2026-08-17'), interval: 2),
            '2026-08-01', '2026-09-20',
            ['2026-08-17', '2026-08-28', '2026-09-14'],
        ];

        yield 'yearly from 29 February' => [
            new RecurrenceRule(Frequency::Yearly, new \DateTimeImmutable('2024-02-29')),
            '2025-01-01', '2028-12-31',
            ['2025-02-28', '2026-02-27', '2027-02-26', '2028-02-29'],
        ];

        yield 'end date is inclusive' => [
            new RecurrenceRule(Frequency::Monthly, new \DateTimeImmutable('2026-01-15'), endDate: new \DateTimeImmutable('2026-03-15')),
            '2026-01-01', '2026-12-31',
            ['2026-01-15', '2026-02-13', '2026-03-13'],
        ];

        yield 'a shift can move a date out of the window, and another into it' => [
            new RecurrenceRule(Frequency::Monthly, new \DateTimeImmutable('2025-12-01')),
            '2026-01-01', '2026-01-31',
            ['2026-01-30'],
        ];

        yield 'rule starting after the window' => [
            new RecurrenceRule(Frequency::Monthly, new \DateTimeImmutable('2027-01-15')),
            '2026-01-01', '2026-12-31',
            [],
        ];
    }

    #[Test]
    public function itRejectsAZeroInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RecurrenceRule(Frequency::Monthly, new \DateTimeImmutable('2026-01-15'), interval: 0);
    }

    #[Test]
    public function itRejectsAnEndDateBeforeTheStartDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RecurrenceRule(Frequency::Monthly, new \DateTimeImmutable('2026-01-15'), endDate: new \DateTimeImmutable('2026-01-14'));
    }
}
