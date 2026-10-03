<?php

namespace App\Forecast;

final class NoStartingBalance extends \DomainException
{
    public static function before(\DateTimeImmutable $date): self
    {
        return new self(\sprintf('There is no balance checkpoint on or before %s to forecast from.', $date->format('Y-m-d')));
    }
}
