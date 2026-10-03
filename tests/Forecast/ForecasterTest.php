<?php

namespace App\Tests\Forecast;

use App\Entity\AdHocTransaction;
use App\Entity\BalanceCheckpoint;
use App\Entity\RecurringTransaction;
use App\Forecast\ForecastDay;
use App\Forecast\Forecaster;
use App\Forecast\NoStartingBalance;
use App\Forecast\Recurrence\Frequency;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ForecasterTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    #[Test]
    public function itForecastsFromStoredCheckpointsRecurringAndAdHocTransactions(): void
    {
        $this->persist(
            new BalanceCheckpoint(self::date('2026-10-01'), 10000),
            new RecurringTransaction('Salary', 250000, Frequency::LastWorkingDayOfMonth, self::date('2026-01-01')),
            new RecurringTransaction('Rent', -90000, Frequency::Monthly, self::date('2026-01-01')),
            new AdHocTransaction('New tyres', -40000, self::date('2026-10-15')),
        );

        $days = $this->forecaster()->forecast(self::date('2026-10-01'), self::date('2026-11-30'));

        self::assertCount(61, $days);
        $byDate = self::byDate($days);
        self::assertSame(10000, $byDate['2026-10-01']->balancePence, 'October rent is already in the checkpoint');
        self::assertSame(10000, $byDate['2026-10-14']->balancePence);
        self::assertSame(-30000, $byDate['2026-10-15']->balancePence);
        self::assertSame(130000, $byDate['2026-10-30']->balancePence, 'Salary, and November rent moved back from Sunday 1st');
        self::assertSame(['Salary', 'Rent'], array_map(static fn ($m) => $m->description, $byDate['2026-10-30']->movements));
        self::assertSame(380000, $byDate['2026-11-30']->balancePence);
    }

    #[Test]
    public function aLaterCheckpointCorrectsTheForecast(): void
    {
        $this->persist(
            new BalanceCheckpoint(self::date('2026-10-01'), 10000),
            new BalanceCheckpoint(self::date('2026-11-02'), 125000),
            new RecurringTransaction('Salary', 250000, Frequency::LastWorkingDayOfMonth, self::date('2026-01-01')),
        );

        $byDate = self::byDate($this->forecaster()->forecast(self::date('2026-10-01'), self::date('2026-11-30')));

        self::assertSame(260000, $byDate['2026-11-01']->balancePence);
        self::assertSame(125000, $byDate['2026-11-02']->balancePence);
        self::assertSame(375000, $byDate['2026-11-30']->balancePence);
    }

    #[Test]
    public function recurringTransactionsStopAfterTheirEndDate(): void
    {
        $this->persist(
            new BalanceCheckpoint(self::date('2026-10-01'), 10000),
            new RecurringTransaction('Phone contract', -2500, Frequency::Monthly, self::date('2025-10-10'), endDate: self::date('2026-10-10')),
        );

        $byDate = self::byDate($this->forecaster()->forecast(self::date('2026-10-01'), self::date('2026-11-30')));

        self::assertSame(7500, $byDate['2026-11-30']->balancePence);
    }

    #[Test]
    public function aOneOffAmountChangesJustThatOccurrence(): void
    {
        $rent = new RecurringTransaction('Rent', -90000, Frequency::Monthly, self::date('2026-01-01'));
        $rent->overrideAmount(self::date('2026-11-01'), -95000);
        $this->persist(new BalanceCheckpoint(self::date('2026-10-01'), 300000), $rent);

        $byDate = self::byDate($this->forecaster()->forecast(self::date('2026-10-01'), self::date('2026-12-31')));

        self::assertSame(-95000, $byDate['2026-10-30']->movements[0]->amountPence, 'November rent, paid early because the 1st is a Sunday');
        self::assertSame(205000, $byDate['2026-10-30']->balancePence);
        self::assertSame(-90000, $byDate['2026-12-01']->movements[0]->amountPence);
        self::assertSame(115000, $byDate['2026-12-01']->balancePence);
    }

    #[Test]
    public function aZeroOneOffSkipsThatOccurrence(): void
    {
        $gym = new RecurringTransaction('Gym', -3000, Frequency::Monthly, self::date('2026-01-15'));
        $gym->overrideAmount(self::date('2026-10-15'), 0);
        $this->persist(new BalanceCheckpoint(self::date('2026-10-01'), 10000), $gym);

        $byDate = self::byDate($this->forecaster()->forecast(self::date('2026-10-01'), self::date('2026-11-30')));

        self::assertSame(10000, $byDate['2026-10-15']->balancePence);
        self::assertSame(7000, $byDate['2026-11-16']->balancePence);
    }

    #[Test]
    public function aOneOffDateMovesJustThatOccurrence(): void
    {
        $rent = new RecurringTransaction('Rent', -90000, Frequency::Monthly, self::date('2026-01-01'));
        $rent->moveOccurrence(self::date('2026-12-01'), self::date('2026-12-04'));
        $this->persist(new BalanceCheckpoint(self::date('2026-10-01'), 300000), $rent);

        $byDate = self::byDate($this->forecaster()->forecast(self::date('2026-10-01'), self::date('2027-01-31')));

        self::assertSame([], $byDate['2026-12-01']->movements);
        self::assertSame(210000, $byDate['2026-12-03']->balancePence);
        $moved = $byDate['2026-12-04']->movements[0];
        self::assertSame(-90000, $moved->amountPence);
        self::assertEquals(self::date('2026-12-01'), $moved->scheduledDate);
        self::assertEquals(self::date('2026-12-01'), $moved->usualDate);
        self::assertSame(120000, $byDate['2026-12-04']->balancePence);
        self::assertFalse($byDate['2026-12-31']->movements[0]->isOneOffDate(), 'January rent, early for New Year');
    }

    #[Test]
    public function aOneOffDateCanMoveAnOccurrenceIntoOrOutOfTheRange(): void
    {
        $rent = new RecurringTransaction('Rent', -90000, Frequency::Monthly, self::date('2026-01-01'));
        $rent->moveOccurrence(self::date('2026-11-01'), self::date('2026-12-02'));
        $rent->moveOccurrence(self::date('2026-12-01'), self::date('2026-11-27'));
        $this->persist(new BalanceCheckpoint(self::date('2026-10-01'), 300000), $rent);

        $byDate = self::byDate($this->forecaster()->forecast(self::date('2026-10-01'), self::date('2026-11-30')));

        self::assertSame([], $byDate['2026-10-30']->movements, 'November rent moved out to 2 Dec');
        self::assertEquals(self::date('2026-12-01'), $byDate['2026-11-27']->movements[0]->scheduledDate, 'December rent moved in');
        self::assertSame(210000, $byDate['2026-11-30']->balancePence);
    }

    #[Test]
    public function hiddenTransactionsStillCountTowardsTheBalance(): void
    {
        $savings = new RecurringTransaction('Savings', -20000, Frequency::Monthly, self::date('2026-10-05'));
        $savings->setHiddenFromForecast(true);
        $gift = new AdHocTransaction('Surprise present', -5000, self::date('2026-10-05'));
        $gift->setHiddenFromForecast(true);
        $this->persist(new BalanceCheckpoint(self::date('2026-10-01'), 100000), $savings, $gift);

        $day = self::byDate($this->forecaster()->forecast(self::date('2026-10-01'), self::date('2026-10-05')))['2026-10-05'];

        self::assertSame(75000, $day->balancePence);
        self::assertSame([], $day->visibleMovements());
        self::assertTrue($day->movements[0]->hidden);
        self::assertTrue($day->movements[1]->hidden);
    }

    #[Test]
    public function itNeedsAStartingBalance(): void
    {
        $this->expectException(NoStartingBalance::class);

        $this->forecaster()->forecast(self::date('2026-10-01'), self::date('2026-11-30'));
    }

    private function forecaster(): Forecaster
    {
        return self::getContainer()->get(Forecaster::class);
    }

    private function persist(object ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();
        $this->em->clear();
    }

    private static function date(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }

    /**
     * @param list<ForecastDay> $days
     *
     * @return array<string, ForecastDay>
     */
    private static function byDate(array $days): array
    {
        return array_combine(array_map(static fn (ForecastDay $d) => $d->date->format('Y-m-d'), $days), $days);
    }
}
