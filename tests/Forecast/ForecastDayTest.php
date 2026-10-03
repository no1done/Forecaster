<?php

namespace App\Tests\Forecast;

use App\Forecast\ForecastDay;
use App\Forecast\Movement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ForecastDayTest extends TestCase
{
    #[Test]
    public function hiddenMovementsAreLeftOutOfTheVisibleOnes(): void
    {
        $date = new \DateTimeImmutable('2026-10-01');
        $rent = new Movement($date, -90000, 'Rent');
        $savings = new Movement($date, -20000, 'Savings', hidden: true);
        $day = new ForecastDay($date, 10000, [$rent, $savings], false);

        self::assertSame([$rent], $day->visibleMovements());
        self::assertSame([$rent, $savings], $day->movements);
    }
}
