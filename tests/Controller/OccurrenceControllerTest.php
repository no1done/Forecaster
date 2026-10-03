<?php

namespace App\Tests\Controller;

use App\Entity\BalanceCheckpoint;
use App\Entity\RecurringTransaction;
use App\Forecast\Recurrence\Frequency;
use PHPUnit\Framework\Attributes\Test;

final class OccurrenceControllerTest extends BrowserTestCase
{
    private RecurringTransaction $rent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rent = new RecurringTransaction('Rent', -90000, Frequency::Monthly, self::date('2026-01-01'));
        $this->persist(new BalanceCheckpoint(self::date('2026-09-30'), 300000), $this->rent);
    }

    #[Test]
    public function theForecastLinksEachRecurringPaymentToItsOccurrence(): void
    {
        $crawler = $this->client->request('GET', '/');

        $link = $crawler->filter('tr[data-date="2026-10-30"]')->selectLink('Rent');
        self::assertSame($this->occurrenceUrl('2026-11-01'), $link->attr('href'), 'November rent, paid early on Friday');
    }

    #[Test]
    public function itChangesTheAmountForOneOccurrence(): void
    {
        $this->client->request('GET', $this->occurrenceUrl('2026-11-01'));
        self::assertSelectorTextContains('main', '-£900.00');

        $this->client->submitForm('Save', ['occurrence[amountPence]' => '950.00']);

        self::assertResponseRedirects('/#day-2026-10-30');
        $rent = $this->reloadRent();
        self::assertSame(-95000, $rent->getAmountPenceFor(self::date('2026-11-01')));
        self::assertSame(-90000, $rent->getAmountPenceFor(self::date('2026-12-01')));

        $crawler = $this->client->request('GET', '/');
        $payday = $crawler->filter('tr[data-date="2026-10-30"]');
        self::assertStringContainsString('-£950.00', $payday->text());
        self::assertStringContainsString('usually -£900.00', $payday->text());
    }

    #[Test]
    public function zeroSkipsTheOccurrence(): void
    {
        $this->client->request('GET', $this->occurrenceUrl('2026-12-01'));
        $this->client->submitForm('Save', ['occurrence[amountPence]' => '0']);

        self::assertSame(0, $this->reloadRent()->getAmountPenceFor(self::date('2026-12-01')));
    }

    #[Test]
    public function itCanBeResetToTheUsualAmount(): void
    {
        $this->reloadRent()->overrideAmount(self::date('2026-11-01'), -95000);
        $this->em()->flush();

        $this->client->request('GET', $this->occurrenceUrl('2026-11-01'));
        self::assertInputValueSame('occurrence[amountPence]', '950.00');
        $this->client->submitForm('Use usual amount');

        self::assertResponseRedirects('/#day-2026-10-30');
        self::assertCount(0, $this->reloadRent()->getAmountOverrides());
    }

    #[Test]
    public function aDateTheRuleIsNotScheduledOnIsNotFound(): void
    {
        $this->client->request('GET', $this->occurrenceUrl('2026-11-02'));

        self::assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function itRejectsABlankAmount(): void
    {
        $this->client->request('GET', $this->occurrenceUrl('2026-11-01'));
        $this->client->submitForm('Save', ['occurrence[amountPence]' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main form', 'Enter an amount, or 0 to skip this one.');
    }

    private function occurrenceUrl(string $scheduledDate): string
    {
        return \sprintf('/recurring/%d/occurrences/%s', $this->rent->getId(), $scheduledDate);
    }

    private function reloadRent(): RecurringTransaction
    {
        return $this->all(RecurringTransaction::class)[0];
    }
}
