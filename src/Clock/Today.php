<?php

namespace App\Clock;

use Psr\Clock\ClockInterface;

/**
 * Today's date in the UK, as a midnight date comparable with the dates
 * Doctrine loads.
 */
final class Today
{
    private const string TIMEZONE = 'Europe/London';

    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function date(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->clock->now()->setTimezone(new \DateTimeZone(self::TIMEZONE))->format('Y-m-d'));
    }
}
