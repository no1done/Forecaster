<?php

namespace App\Tests\Controller;

use App\Entity\AdHocTransaction;
use PHPUnit\Framework\Attributes\Test;

final class AdHocControllerTest extends BrowserTestCase
{
    #[Test]
    public function itAddsAPurchaseOnTheChosenDayAndReturnsToThatDayOnTheForecast(): void
    {
        $this->client->request('GET', '/ad-hoc/new?date=2026-10-15');
        self::assertInputValueSame('ad_hoc[date]', '2026-10-15');
        self::assertCheckboxChecked('ad_hoc[amountPence][direction]', 'Money out is the default');

        $this->client->submitForm('Save', [
            'ad_hoc[description]' => 'New tyres',
            'ad_hoc[amountPence][direction]' => 'out',
            'ad_hoc[amountPence][amount]' => '400.00',
            'ad_hoc[date]' => '2026-10-15',
        ]);

        self::assertResponseRedirects('/#day-2026-10-15');
        $transactions = $this->all(AdHocTransaction::class);
        self::assertCount(1, $transactions);
        self::assertSame('New tyres', $transactions[0]->getDescription());
        self::assertSame(-40000, $transactions[0]->getAmountPence());
        self::assertSame('2026-10-15', $transactions[0]->getDate()->format('Y-m-d'));
    }

    #[Test]
    public function itDefaultsToToday(): void
    {
        $this->client->request('GET', '/ad-hoc/new');

        self::assertInputValueSame('ad_hoc[date]', '2026-10-01');
    }

    #[Test]
    public function moneyInIsStoredAsPositive(): void
    {
        $this->client->request('GET', '/ad-hoc/new');
        $this->client->submitForm('Save', [
            'ad_hoc[description]' => 'Sold bike',
            'ad_hoc[amountPence][direction]' => 'in',
            'ad_hoc[amountPence][amount]' => '150',
        ]);

        self::assertSame(15000, $this->all(AdHocTransaction::class)[0]->getAmountPence());
    }

    #[Test]
    public function itRejectsMissingDetails(): void
    {
        $this->client->request('GET', '/ad-hoc/new');
        $this->client->submitForm('Save', [
            'ad_hoc[description]' => '',
            'ad_hoc[amountPence][amount]' => '0',
            'ad_hoc[date]' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main form', 'Enter a description.');
        self::assertSelectorTextContains('main form', 'Enter an amount above zero.');
        self::assertSelectorTextContains('main form', 'Enter the date.');
        self::assertCount(0, $this->all(AdHocTransaction::class));
    }

    #[Test]
    public function itEditsAPurchase(): void
    {
        $this->persist($tyres = new AdHocTransaction('New tyres', -40000, self::date('2026-10-15')));

        $this->client->request('GET', '/ad-hoc/'.$tyres->getId().'/edit');
        self::assertInputValueSame('ad_hoc[amountPence][amount]', '400.00');
        $this->client->submitForm('Save', ['ad_hoc[amountPence][amount]' => '380.50']);

        self::assertResponseRedirects('/#day-2026-10-15');
        self::assertSame(-38050, $this->all(AdHocTransaction::class)[0]->getAmountPence());
    }

    #[Test]
    public function itListsUpcomingPurchases(): void
    {
        $this->persist(
            new AdHocTransaction('Old purchase', -1000, self::date('2026-09-01')),
            new AdHocTransaction('New tyres', -40000, self::date('2026-10-15')),
        );

        $this->client->request('GET', '/ad-hoc');

        self::assertSelectorTextContains('#upcoming', 'New tyres');
        self::assertSelectorTextContains('#upcoming', '-£400.00');
        self::assertSelectorTextNotContains('#upcoming', 'Old purchase');
        self::assertSelectorTextContains('#past', 'Old purchase');
    }

    #[Test]
    public function itDeletesAPurchase(): void
    {
        $this->persist(new AdHocTransaction('New tyres', -40000, self::date('2026-10-15')));

        $this->client->request('GET', '/ad-hoc');
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/ad-hoc');
        self::assertCount(0, $this->all(AdHocTransaction::class));
    }
}
