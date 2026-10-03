<?php

namespace App\Tests\Forecast;

use App\Forecast\Checkpoint;
use App\Forecast\ForecastCalculator;
use App\Forecast\ForecastDay;
use App\Forecast\Movement;
use App\Forecast\NoStartingBalance;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ForecastCalculatorTest extends TestCase
{
    #[Test]
    public function theStartingBalanceCarriesForwardWhenNothingHappens(): void
    {
        $days = (new ForecastCalculator())->calculate(
            [self::checkpoint('2026-10-01', 10000)],
            [],
            self::date('2026-10-01'),
            self::date('2026-10-03'),
        );

        self::assertSame(
            ['2026-10-01' => 10000, '2026-10-02' => 10000, '2026-10-03' => 10000],
            self::balances($days),
        );
        self::assertTrue($days[0]->isCheckpoint);
        self::assertFalse($days[1]->isCheckpoint);
    }

    #[Test]
    public function movementsAreAppliedOnTheirDayAsARunningTotal(): void
    {
        $rent = self::movement('2026-10-03', -90000, 'Rent');
        $salary = self::movement('2026-10-02', 250000, 'Salary');
        $coffee = self::movement('2026-10-03', -350, 'Coffee');

        $days = (new ForecastCalculator())->calculate(
            [self::checkpoint('2026-10-01', 10000)],
            [$rent, $salary, $coffee],
            self::date('2026-10-01'),
            self::date('2026-10-04'),
        );

        self::assertSame(
            ['2026-10-01' => 10000, '2026-10-02' => 260000, '2026-10-03' => 169650, '2026-10-04' => 169650],
            self::balances($days),
        );
        self::assertSame([$salary], $days[1]->movements);
        self::assertSame([$rent, $coffee], $days[2]->movements);
    }

    #[Test]
    public function movementsOnOrBeforeTheCheckpointAreAlreadyInItsBalance(): void
    {
        $days = (new ForecastCalculator())->calculate(
            [self::checkpoint('2026-10-02', 10000)],
            [self::movement('2026-10-01', -5000), self::movement('2026-10-02', -5000)],
            self::date('2026-10-02'),
            self::date('2026-10-03'),
        );

        self::assertSame(['2026-10-02' => 10000, '2026-10-03' => 10000], self::balances($days));
    }

    #[Test]
    public function aLaterCheckpointResetsTheBalanceAtTheEndOfItsDay(): void
    {
        $days = (new ForecastCalculator())->calculate(
            [self::checkpoint('2026-10-03', 5000), self::checkpoint('2026-10-01', 10000)],
            [
                self::movement('2026-10-02', -2000),
                self::movement('2026-10-03', -1000),
                self::movement('2026-10-04', -500),
            ],
            self::date('2026-10-01'),
            self::date('2026-10-04'),
        );

        self::assertSame(
            ['2026-10-01' => 10000, '2026-10-02' => 8000, '2026-10-03' => 5000, '2026-10-04' => 4500],
            self::balances($days),
        );
        self::assertTrue($days[2]->isCheckpoint);
    }

    #[Test]
    public function movementsBetweenTheCheckpointAndTheWindowStillCount(): void
    {
        $days = (new ForecastCalculator())->calculate(
            [self::checkpoint('2026-09-28', 10000)],
            [self::movement('2026-09-29', -3000)],
            self::date('2026-10-01'),
            self::date('2026-10-01'),
        );

        self::assertSame(['2026-10-01' => 7000], self::balances($days));
    }

    #[Test]
    public function itCanGoOverdrawn(): void
    {
        $days = (new ForecastCalculator())->calculate(
            [self::checkpoint('2026-10-01', 1000)],
            [self::movement('2026-10-02', -1500)],
            self::date('2026-10-01'),
            self::date('2026-10-02'),
        );

        self::assertSame(['2026-10-01' => 1000, '2026-10-02' => -500], self::balances($days));
    }

    #[Test]
    public function itNeedsACheckpointOnOrBeforeTheStartOfTheWindow(): void
    {
        $this->expectException(NoStartingBalance::class);

        (new ForecastCalculator())->calculate(
            [self::checkpoint('2026-10-02', 10000)],
            [],
            self::date('2026-10-01'),
            self::date('2026-10-03'),
        );
    }

    private static function date(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }

    private static function checkpoint(string $date, int $balancePence): Checkpoint
    {
        return new Checkpoint(self::date($date), $balancePence);
    }

    private static function movement(string $date, int $amountPence, string $description = 'Something'): Movement
    {
        return new Movement(self::date($date), $amountPence, $description);
    }

    /**
     * @param list<ForecastDay> $days
     *
     * @return array<string, int>
     */
    private static function balances(array $days): array
    {
        $balances = [];
        foreach ($days as $day) {
            $balances[$day->date->format('Y-m-d')] = $day->balancePence;
        }

        return $balances;
    }
}
