<?php

namespace App\Tests\Controller;

use App\Entity\BalanceCheckpoint;
use PHPUnit\Framework\Attributes\Test;

final class BalanceControllerTest extends BrowserTestCase
{
    #[Test]
    public function itRecordsABalance(): void
    {
        $this->client->request('GET', '/balances');
        self::assertResponseIsSuccessful();
        self::assertInputValueSame('checkpoint[date]', '2026-10-01');

        $this->client->submitForm('Record balance', [
            'checkpoint[date]' => '2026-09-30',
            'checkpoint[balancePence]' => '1234.56',
        ]);

        self::assertResponseRedirects('/balances');
        $checkpoints = $this->all(BalanceCheckpoint::class);
        self::assertCount(1, $checkpoints);
        self::assertSame('2026-09-30', $checkpoints[0]->getDate()->format('Y-m-d'));
        self::assertSame(123456, $checkpoints[0]->getBalancePence());

        $this->client->followRedirect();
        self::assertSelectorTextContains('table', '£1,234.56');
    }

    #[Test]
    public function anOverdrawnBalanceCanBeRecorded(): void
    {
        $this->client->request('GET', '/balances');
        $this->client->submitForm('Record balance', [
            'checkpoint[date]' => '2026-09-30',
            'checkpoint[balancePence]' => '-50.25',
        ]);

        self::assertSame(-5025, $this->all(BalanceCheckpoint::class)[0]->getBalancePence());
    }

    #[Test]
    public function recordingADateAgainUpdatesThatDay(): void
    {
        $this->persist(new BalanceCheckpoint(self::date('2026-09-30'), 10000));

        $this->client->request('GET', '/balances');
        $this->client->submitForm('Record balance', [
            'checkpoint[date]' => '2026-09-30',
            'checkpoint[balancePence]' => '200.00',
        ]);

        $checkpoints = $this->all(BalanceCheckpoint::class);
        self::assertCount(1, $checkpoints);
        self::assertSame(20000, $checkpoints[0]->getBalancePence());
    }

    #[Test]
    public function itRejectsABlankDateOrBalance(): void
    {
        $this->client->request('GET', '/balances');
        $this->client->submitForm('Record balance', [
            'checkpoint[date]' => '',
            'checkpoint[balancePence]' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main form', 'Enter the date.');
        self::assertSelectorTextContains('main form', 'Enter the balance.');
        self::assertCount(0, $this->all(BalanceCheckpoint::class));
    }

    #[Test]
    public function aBalanceCanBeDeleted(): void
    {
        $this->persist(new BalanceCheckpoint(self::date('2026-09-30'), 10000));

        $this->client->request('GET', '/balances');
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/balances');
        self::assertCount(0, $this->all(BalanceCheckpoint::class));
    }

    #[Test]
    public function aDeleteFromAnotherSiteIsRefused(): void
    {
        $this->persist($checkpoint = new BalanceCheckpoint(self::date('2026-09-30'), 10000));

        $this->client->request('POST', '/balances/'.$checkpoint->getId().'/delete', ['_token' => 'csrf-token'], server: ['HTTP_ORIGIN' => 'https://evil.example']);

        self::assertResponseRedirects('/login', message: 'an invalid CSRF token is treated as needing to authenticate again');
        self::assertCount(1, $this->all(BalanceCheckpoint::class));
    }

    #[Test]
    public function theForecastLinksHereWhenThereIsNoStartingBalance(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertSame('/balances', $crawler->selectLink('Record your starting balance')->attr('href'));
    }
}
