<?php

namespace App\Tests\Forecast;

use App\Forecast\BalanceColour;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BalanceColourTest extends TestCase
{
    #[Test]
    #[DataProvider('balances')]
    public function itColoursABalance(int $balancePence, string $expected): void
    {
        self::assertSame($expected, (new BalanceColour())->forBalance($balancePence));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function balances(): iterable
    {
        yield 'overdrawn by a penny is red' => [-1, BalanceColour::RED];
        yield 'overdrawn by a lot is red' => [-100000, BalanceColour::RED];
        yield 'exactly zero is amber' => [0, BalanceColour::AMBER];
        yield '£125 is halfway from amber to light green' => [12500, '#d1bd72'];
        yield '£249.99 is all but light green' => [24999, '#c9d6a3'];
        yield '£250 is dark green' => [25000, BalanceColour::DARK_GREEN];
        yield 'well above £250 is dark green' => [1000000, BalanceColour::DARK_GREEN];
    }
}
