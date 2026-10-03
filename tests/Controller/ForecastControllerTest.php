<?php

namespace App\Tests\Controller;

use App\Entity\AdHocTransaction;
use App\Entity\BalanceCheckpoint;
use App\Entity\RecurringTransaction;
use App\Forecast\BalanceColour;
use App\Forecast\Recurrence\Frequency;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class ForecastControllerTest extends WebTestCase
{
    use ClockSensitiveTrait;

    #[Test]
    public function itShowsTwelveMonthsOfDailyBalancesFromToday(): void
    {
        $client = self::createClient();
        self::mockTime('2026-10-01 09:00');
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist(new BalanceCheckpoint(new \DateTimeImmutable('2026-09-30'), 10000));
        $em->persist(new RecurringTransaction('Salary', 250000, Frequency::LastWorkingDayOfMonth, new \DateTimeImmutable('2026-01-01')));
        $em->persist(new AdHocTransaction('New tyres', -40000, new \DateTimeImmutable('2026-10-15')));
        $em->flush();

        $crawler = $client->request('GET', '/');

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
        $client = self::createClient();
        self::mockTime('2026-10-01 09:00');

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'starting balance');
        self::assertSelectorNotExists('tr[data-date]');
    }
}
