<?php

namespace App\Tests\Twig;

use App\Twig\MoneyExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MoneyExtensionTest extends TestCase
{
    #[Test]
    #[DataProvider('amounts')]
    public function itFormatsPenceAsPounds(int $pence, string $expected): void
    {
        self::assertSame($expected, MoneyExtension::formatPence($pence));
    }

    /**
     * @return iterable<array{int, string}>
     */
    public static function amounts(): iterable
    {
        yield [0, '£0.00'];
        yield [5, '£0.05'];
        yield [350, '£3.50'];
        yield [123456, '£1,234.56'];
        yield [100000000, '£1,000,000.00'];
        yield [-350, '-£3.50'];
        yield [-123456, '-£1,234.56'];
    }
}
