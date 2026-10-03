<?php

namespace App\Tests\Entity;

use App\Entity\RecurringTransaction;
use App\Forecast\Recurrence\Frequency;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RecurringTransactionTest extends TestCase
{
    #[Test]
    public function everyOccurrenceUsesTheUsualAmountByDefault(): void
    {
        self::assertSame(-9000, self::electricity()->getAmountPenceFor(self::date('2026-11-01')));
    }

    #[Test]
    public function itIsShownOnTheForecastByDefault(): void
    {
        self::assertFalse(self::electricity()->isHiddenFromForecast());
    }

    #[Test]
    public function aOneOffAmountAppliesToThatOccurrenceOnly(): void
    {
        $electricity = self::electricity();

        $electricity->overrideAmount(self::date('2026-11-01'), -12000);

        self::assertSame(-12000, $electricity->getAmountPenceFor(self::date('2026-11-01')));
        self::assertSame(-9000, $electricity->getAmountPenceFor(self::date('2026-12-01')));
    }

    #[Test]
    public function aOneOffAmountCanBeChangedAgain(): void
    {
        $electricity = self::electricity();

        $electricity->overrideAmount(self::date('2026-11-01'), -12000);
        $electricity->overrideAmount(self::date('2026-11-01'), -11000);

        self::assertSame(-11000, $electricity->getAmountPenceFor(self::date('2026-11-01')));
        self::assertCount(1, $electricity->getAmountOverrides());
    }

    #[Test]
    public function aZeroAmountSkipsThatOccurrence(): void
    {
        $electricity = self::electricity();

        $electricity->overrideAmount(self::date('2026-11-01'), 0);

        self::assertSame(0, $electricity->getAmountPenceFor(self::date('2026-11-01')));
    }

    #[Test]
    public function settingTheUsualAmountRemovesTheOneOff(): void
    {
        $electricity = self::electricity();
        $electricity->overrideAmount(self::date('2026-11-01'), -12000);

        $electricity->overrideAmount(self::date('2026-11-01'), -9000);

        self::assertCount(0, $electricity->getAmountOverrides());
        self::assertSame(-9000, $electricity->getAmountPenceFor(self::date('2026-11-01')));
    }

    #[Test]
    public function aOneOffMustBeForADateTheRuleIsScheduledOn(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        self::electricity()->overrideAmount(self::date('2026-11-02'), -12000);
    }

    #[Test]
    public function changingTheUsualAmountLeavesOneOffsAlone(): void
    {
        $electricity = self::electricity();
        $electricity->overrideAmount(self::date('2026-11-01'), -12000);

        $electricity->setAmountPence(-9500);

        self::assertSame(-12000, $electricity->getAmountPenceFor(self::date('2026-11-01')));
        self::assertSame(-9500, $electricity->getAmountPenceFor(self::date('2026-12-01')));
    }

    private static function electricity(): RecurringTransaction
    {
        return new RecurringTransaction('Electricity', -9000, Frequency::Monthly, self::date('2026-01-01'));
    }

    private static function date(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }
}
