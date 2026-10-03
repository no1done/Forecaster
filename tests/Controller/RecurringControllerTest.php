<?php

namespace App\Tests\Controller;

use App\Entity\RecurringOccurrenceOverride;
use App\Entity\RecurringTransaction;
use App\Forecast\Recurrence\Frequency;
use PHPUnit\Framework\Attributes\Test;

final class RecurringControllerTest extends BrowserTestCase
{
    #[Test]
    public function itAddsARecurringTransaction(): void
    {
        $this->client->request('GET', '/recurring/new');
        $this->client->submitForm('Save', [
            'recurring[description]' => 'Rent',
            'recurring[amountPence][direction]' => 'out',
            'recurring[amountPence][amount]' => '900.00',
            'recurring[frequency]' => 'monthly',
            'recurring[interval]' => '1',
            'recurring[startDate]' => '2026-01-01',
            'recurring[endDate]' => '',
        ]);

        self::assertResponseRedirects('/recurring');
        $all = $this->all(RecurringTransaction::class);
        self::assertCount(1, $all);
        self::assertSame('Rent', $all[0]->getDescription());
        self::assertSame(-90000, $all[0]->getAmountPence());
        self::assertSame(Frequency::Monthly, $all[0]->getFrequency());
        self::assertSame(1, $all[0]->getInterval());
        self::assertSame('2026-01-01', $all[0]->getStartDate()->format('Y-m-d'));
        self::assertNull($all[0]->getEndDate());
    }

    #[Test]
    public function itAddsAFortnightlyIncomeWithAnEndDate(): void
    {
        $this->client->request('GET', '/recurring/new');
        $this->client->submitForm('Save', [
            'recurring[description]' => 'Lodger',
            'recurring[amountPence][direction]' => 'in',
            'recurring[amountPence][amount]' => '250',
            'recurring[frequency]' => 'weekly',
            'recurring[interval]' => '2',
            'recurring[startDate]' => '2026-10-02',
            'recurring[endDate]' => '2027-03-31',
        ]);

        $lodger = $this->all(RecurringTransaction::class)[0];
        self::assertSame(25000, $lodger->getAmountPence());
        self::assertSame(2, $lodger->getInterval());
        self::assertSame('2027-03-31', $lodger->getEndDate()?->format('Y-m-d'));
    }

    #[Test]
    public function itCanBeHiddenFromTheForecastAndShownAgain(): void
    {
        $this->persist($savings = new RecurringTransaction('Savings', -20000, Frequency::Monthly, self::date('2026-10-05')));

        $this->client->request('GET', '/recurring/'.$savings->getId().'/edit');
        self::assertCheckboxNotChecked('recurring[hiddenFromForecast]');
        $this->client->submitForm('Save', ['recurring[hiddenFromForecast]' => true]);

        self::assertTrue($this->all(RecurringTransaction::class)[0]->isHiddenFromForecast());
        $this->client->request('GET', '/recurring');
        self::assertSelectorTextContains('table', 'Hidden from forecast');

        $this->client->request('GET', '/recurring/'.$savings->getId().'/edit');
        self::assertCheckboxChecked('recurring[hiddenFromForecast]');
        $this->client->submitForm('Save', ['recurring[hiddenFromForecast]' => false]);

        self::assertFalse($this->all(RecurringTransaction::class)[0]->isHiddenFromForecast());
    }

    #[Test]
    public function itRejectsInvalidDetails(): void
    {
        $this->client->request('GET', '/recurring/new');
        $this->client->submitForm('Save', [
            'recurring[description]' => '',
            'recurring[amountPence][amount]' => '',
            'recurring[interval]' => '0',
            'recurring[startDate]' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main form', 'Enter a description.');
        self::assertSelectorTextContains('main form', 'Enter an amount above zero.');
        self::assertSelectorTextContains('main form', 'Repeat every must be at least 1.');
        self::assertSelectorTextContains('main form', 'Enter the start date.');
        self::assertCount(0, $this->all(RecurringTransaction::class));
    }

    #[Test]
    public function itRejectsAnEndDateBeforeTheStart(): void
    {
        $this->client->request('GET', '/recurring/new');
        $this->client->submitForm('Save', [
            'recurring[description]' => 'Rent',
            'recurring[amountPence][amount]' => '900',
            'recurring[startDate]' => '2026-01-01',
            'recurring[endDate]' => '2025-12-31',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main form', 'The end date must be on or after the start date.');
    }

    #[Test]
    public function itEditsARecurringTransactionAndKeepsItsOneOffs(): void
    {
        $rent = new RecurringTransaction('Rent', -90000, Frequency::Monthly, self::date('2026-01-01'));
        $rent->overrideAmount(self::date('2026-11-01'), -95000);
        $this->persist($rent);

        $this->client->request('GET', '/recurring/'.$rent->getId().'/edit');
        self::assertInputValueSame('recurring[amountPence][amount]', '900.00');
        $this->client->submitForm('Save', ['recurring[amountPence][amount]' => '925.00']);

        self::assertResponseRedirects('/recurring');
        $rent = $this->all(RecurringTransaction::class)[0];
        self::assertSame(-92500, $rent->getAmountPence());
        self::assertSame(-95000, $rent->getAmountPenceFor(self::date('2026-11-01')));
    }

    #[Test]
    public function itListsRecurringTransactions(): void
    {
        $this->persist(
            new RecurringTransaction('Salary', 250000, Frequency::LastWorkingDayOfMonth, self::date('2026-01-01')),
            new RecurringTransaction('Rent', -90000, Frequency::Monthly, self::date('2026-01-01')),
        );

        $this->client->request('GET', '/recurring');

        self::assertSelectorTextContains('table', 'Salary');
        self::assertSelectorTextContains('table', '£2,500.00');
        self::assertSelectorTextContains('table', 'Last working day of the month');
        self::assertSelectorTextContains('table', 'Rent');
        self::assertSelectorTextContains('table', '-£900.00');
    }

    #[Test]
    public function deletingARecurringTransactionDeletesItsOneOffs(): void
    {
        $rent = new RecurringTransaction('Rent', -90000, Frequency::Monthly, self::date('2026-01-01'));
        $rent->overrideAmount(self::date('2026-11-01'), -95000);
        $this->persist($rent);

        $this->client->request('GET', '/recurring');
        // Without this marker the double-submit JS skips the field, and the delete is rejected once the session has used double-submit.
        self::assertSelectorExists('input[name="_token"][data-controller="csrf-protection"]');
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/recurring');
        self::assertCount(0, $this->all(RecurringTransaction::class));
        self::assertCount(0, $this->all(RecurringOccurrenceOverride::class));
    }
}
