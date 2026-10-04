<?php

namespace App\Tests\Controller;

use App\Entity\AdHocTransaction;
use App\Entity\BalanceCheckpoint;
use App\Entity\RecurringTransaction;
use App\Entity\User;
use App\Forecast\Recurrence\Frequency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Each user sees and changes only their own balances and transactions.
 */
final class UserScopeTest extends BrowserTestCase
{
    private User $other;
    private BalanceCheckpoint $theirBalance;
    private RecurringTransaction $theirRent;
    private AdHocTransaction $theirTyres;

    protected function setUp(): void
    {
        parent::setUp();
        $this->other = $this->createUser('someone-else@example.com');
        $other = $this->em()->getReference(User::class, $this->other->getId());
        $this->persist(
            $this->theirBalance = new BalanceCheckpoint($other, self::date('2026-09-30'), 999900),
            $this->theirRent = new RecurringTransaction($other, 'Their rent', -90000, Frequency::Monthly, self::date('2026-01-01')),
            $this->theirTyres = new AdHocTransaction($other, 'Their tyres', -40000, self::date('2026-10-15')),
        );
    }

    #[Test]
    public function anotherUsersStartingBalanceIsNotYours(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertCount(1, $crawler->selectLink('Record your starting balance'));
    }

    #[Test]
    public function theForecastShowsOnlyYourTransactions(): void
    {
        $this->persist(
            new BalanceCheckpoint($this->user(), self::date('2026-09-30'), 10000),
            new AdHocTransaction($this->user(), 'My tyres', -4000, self::date('2026-10-15')),
        );

        $crawler = $this->client->request('GET', '/');

        $day = $crawler->filter('tr[data-date="2026-10-15"]')->text();
        self::assertStringContainsString('My tyres', $day);
        self::assertStringContainsString('£60.00', $day);
        self::assertStringNotContainsString('Their tyres', $crawler->text());
        self::assertStringNotContainsString('Their rent', $crawler->text());
    }

    #[Test]
    #[DataProvider('listPages')]
    public function listsShowOnlyYourRecords(string $url, string $theirs): void
    {
        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextNotContains('main', $theirs);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function listPages(): iterable
    {
        yield ['/balances', '£9,999.00'];
        yield ['/recurring', 'Their rent'];
        yield ['/ad-hoc', 'Their tyres'];
    }

    #[Test]
    public function youCanRecordABalanceOnTheSameDayAsAnotherUser(): void
    {
        $this->client->request('GET', '/balances');
        $this->client->submitForm('Record balance', [
            'checkpoint[date]' => '2026-09-30',
            'checkpoint[balancePence]' => '100.00',
        ]);

        self::assertResponseRedirects('/balances');
        $balances = [];
        foreach ($this->all(BalanceCheckpoint::class) as $checkpoint) {
            $balances[$checkpoint->getOwner()->getEmail()] = $checkpoint->getBalancePence();
        }
        self::assertSame(['someone-else@example.com' => 999900, 'me@example.com' => 10000], $balances);
    }

    #[Test]
    public function newRecordsBelongToYou(): void
    {
        $this->client->request('GET', '/ad-hoc/new');
        $this->client->submitForm('Save', [
            'ad_hoc[description]' => 'My tyres',
            'ad_hoc[amountPence][amount]' => '40.00',
            'ad_hoc[date]' => '2026-10-15',
        ]);

        $mine = array_values(array_filter($this->all(AdHocTransaction::class), static fn (AdHocTransaction $t) => 'My tyres' === $t->getDescription()));
        self::assertSame('me@example.com', $mine[0]->getOwner()->getEmail());
    }

    #[Test]
    public function youCannotOpenAnotherUsersRecords(): void
    {
        foreach ([
            '/recurring/'.$this->theirRent->getId().'/edit',
            '/recurring/'.$this->theirRent->getId().'/occurrences/2026-11-01',
            '/ad-hoc/'.$this->theirTyres->getId().'/edit',
        ] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
    }

    #[Test]
    public function youCannotDeleteAnotherUsersRecords(): void
    {
        foreach ([
            '/balances/'.$this->theirBalance->getId().'/delete',
            '/recurring/'.$this->theirRent->getId().'/delete',
            '/ad-hoc/'.$this->theirTyres->getId().'/delete',
        ] as $url) {
            $this->client->request('POST', $url, ['_token' => 'csrf-token']);
            self::assertResponseStatusCodeSame(403, $url);
        }

        self::assertCount(1, $this->all(BalanceCheckpoint::class));
        self::assertCount(1, $this->all(RecurringTransaction::class));
        self::assertCount(1, $this->all(AdHocTransaction::class));
    }
}
