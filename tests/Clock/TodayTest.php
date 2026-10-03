<?php

namespace App\Tests\Clock;

use App\Clock\Today;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class TodayTest extends TestCase
{
    #[Test]
    public function itIsTheDateInTheUk(): void
    {
        $today = new Today(new MockClock('2026-06-30 23:30', 'UTC'));

        self::assertSame('2026-07-01 00:00:00', $today->date()->format('Y-m-d H:i:s'), 'already tomorrow in BST');
    }

    #[Test]
    public function itIsMidnightInTheDefaultTimezoneLikeDatesFromTheDatabase(): void
    {
        $today = new Today(new MockClock('2026-12-01 12:00', 'UTC'));

        self::assertEquals(new \DateTimeImmutable('2026-12-01'), $today->date());
    }
}
