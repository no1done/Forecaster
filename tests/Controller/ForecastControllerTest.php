<?php

namespace App\Tests\Controller;

use App\Entity\AdHocTransaction;
use App\Entity\BalanceCheckpoint;
use App\Entity\RecurringTransaction;
use App\Forecast\BalanceColour;
use App\Forecast\Recurrence\Frequency;
use PHPUnit\Framework\Attributes\Test;

final class ForecastControllerTest extends BrowserTestCase
{
    #[Test]
    public function itShowsTwelveMonthsOfDailyBalancesFromToday(): void
    {
        $this->persist(
            new BalanceCheckpoint(self::date('2026-09-30'), 10000),
            new RecurringTransaction('Salary', 250000, Frequency::LastWorkingDayOfMonth, self::date('2026-01-01')),
            new AdHocTransaction('New tyres', -40000, self::date('2026-10-15')),
        );

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        $days = $crawler->filter('tr[data-date]');
        self::assertCount(365, $days);
        self::assertSame('2026-10-01', $days->first()->attr('data-date'));
        self::assertSame('2027-09-30', $days->last()->attr('data-date'));

        $tyres = $crawler->filter('tr[data-date="2026-10-15"]');
        self::assertStringContainsString('New tyres', $tyres->text());
        self::assertStringContainsString('-£400.00', $tyres->text());
        self::assertSame('-£300.00', $tyres->filter('.balance')->text());
        self::assertStringContainsString(BalanceColour::RED, $tyres->filter('.balance')->attr('style'));

        $payday = $crawler->filter('tr[data-date="2026-10-30"]');
        self::assertStringContainsString('Salary', $payday->text());
        self::assertSame('£2,200.00', $payday->filter('.balance')->text());
        self::assertStringContainsString(BalanceColour::DARK_GREEN, $payday->filter('.balance')->attr('style'));
    }

    #[Test]
    public function itAsksForAStartingBalanceWhenThereIsNone(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'starting balance');
        self::assertSelectorNotExists('tr[data-date]');
    }
}
