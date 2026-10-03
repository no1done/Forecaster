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
    public function hiddenTransactionsAreNotListedButStillChangeTheBalance(): void
    {
        $savings = new RecurringTransaction('Savings', -20000, Frequency::Monthly, self::date('2026-10-05'));
        $savings->setHiddenFromForecast(true);
        $this->persist(
            new BalanceCheckpoint(self::date('2026-09-30'), 100000),
            $savings,
            new AdHocTransaction('New tyres', -40000, self::date('2026-10-05')),
        );

        $crawler = $this->client->request('GET', '/');

        $day = $crawler->filter('tr[data-date="2026-10-05"]');
        self::assertStringContainsString('New tyres', $day->text());
        self::assertStringNotContainsString('Savings', $day->text());
        self::assertSame('£400.00', $day->filter('.balance')->text());
    }

    #[Test]
    public function itShowsTheDatesPickedFromTheFirstBalanceOnwards(): void
    {
        $this->persist(
            new BalanceCheckpoint(self::date('2026-01-31'), 10000),
            new AdHocTransaction('New tyres', -4000, self::date('2026-02-10')),
        );

        $crawler = $this->client->request('GET', '/?from=2026-02-01&to=2026-03-15');

        self::assertResponseIsSuccessful();
        $days = $crawler->filter('tr[data-date]');
        self::assertCount(43, $days);
        self::assertSame('2026-02-01', $days->first()->attr('data-date'));
        self::assertSame('2026-03-15', $days->last()->attr('data-date'));
        self::assertSame('£60.00', $crawler->filter('tr[data-date="2026-02-10"] .balance')->text());
        self::assertSame('2026-01-31', $crawler->filter('input[name="from"]')->attr('min'));
        self::assertSame('2026-02-01', $crawler->filter('input[name="from"]')->attr('value'));
        // Shown as DD/MM/YYYY by the datepicker, whatever the browser's locale.
        self::assertSame('datepicker', $crawler->filter('input[name="from"]')->attr('data-controller'));
    }

    #[Test]
    public function aStartDateAloneShowsTwelveMonthsFromIt(): void
    {
        $this->persist(new BalanceCheckpoint(self::date('2026-01-31'), 10000));

        $crawler = $this->client->request('GET', '/?from=2026-03-01&to=');

        $days = $crawler->filter('tr[data-date]');
        self::assertSame('2026-03-01', $days->first()->attr('data-date'));
        self::assertSame('2027-02-28', $days->last()->attr('data-date'));
        self::assertSame('2027-02-28', $crawler->filter('input[name="to"]')->attr('value'));
    }

    #[Test]
    public function datesBeforeTheFirstBalanceAreRejected(): void
    {
        $this->persist(new BalanceCheckpoint(self::date('2026-09-30'), 10000));

        $crawler = $this->client->request('GET', '/?from=2026-09-01&to=2026-12-31');

        self::assertResponseIsUnprocessable();
        self::assertSelectorTextContains('form.filters', 'starts from your first balance, on 30 Sep 2026');
        // Falls back to the usual twelve months from today.
        self::assertSame('2026-10-01', $crawler->filter('tr[data-date]')->first()->attr('data-date'));
    }

    #[Test]
    public function anEndDateBeforeTheStartIsRejected(): void
    {
        $this->persist(new BalanceCheckpoint(self::date('2026-09-30'), 10000));

        $this->client->request('GET', '/?from=2026-12-01&to=2026-11-01');

        self::assertSelectorTextContains('form.filters', 'on or after the start date');
    }

    #[Test]
    public function searchingListsOnlyDaysWithMatchingTransactions(): void
    {
        $this->persist(
            new BalanceCheckpoint(self::date('2026-09-30'), 10000),
            new RecurringTransaction('Salary', 250000, Frequency::LastWorkingDayOfMonth, self::date('2026-01-01')),
            new AdHocTransaction('New tyres', -40000, self::date('2026-10-15')),
        );

        $crawler = $this->client->request('GET', '/?from=2026-10-01&to=2026-12-31&search=salary');

        $days = $crawler->filter('tr[data-date]');
        self::assertSame(['2026-10-30', '2026-11-30', '2026-12-31'], $days->each(static fn ($row) => $row->attr('data-date')));
        // Balances still include everything else.
        self::assertSame('£2,200.00', $days->first()->filter('.balance')->text());
        self::assertCount(3, $crawler->filter('tr.month'));
    }

    #[Test]
    public function aSearchWithNoMatchesSaysSo(): void
    {
        $this->persist(new BalanceCheckpoint(self::date('2026-09-30'), 10000));

        $this->client->request('GET', '/?search=lottery');

        self::assertSelectorNotExists('tr[data-date]');
        self::assertSelectorTextContains('.notice', 'No transactions match “lottery”');
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
