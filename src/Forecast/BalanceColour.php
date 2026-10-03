<?php

namespace App\Forecast;

/**
 * Red when overdrawn, amber at £0 fading to light green as the balance
 * approaches the comfortable threshold, then dark green.
 */
final class BalanceColour
{
    public const string RED = '#d9826b'; // terracotta
    public const string AMBER = '#d9a441'; // ochre
    public const string LIGHT_GREEN = '#c9d6a3'; // sage
    public const string DARK_GREEN = '#86a35f'; // moss

    public function __construct(
        private readonly int $comfortablePence = 25000,
    ) {
    }

    public function forBalance(int $balancePence): string
    {
        if ($balancePence < 0) {
            return self::RED;
        }
        if ($balancePence >= $this->comfortablePence) {
            return self::DARK_GREEN;
        }

        return self::blend(self::AMBER, self::LIGHT_GREEN, $balancePence / $this->comfortablePence);
    }

    private static function blend(string $from, string $to, float $ratio): string
    {
        $from = sscanf($from, '#%02x%02x%02x');
        $to = sscanf($to, '#%02x%02x%02x');

        $channels = array_map(
            static fn (int $a, int $b) => (int) round($a + ($b - $a) * $ratio),
            $from,
            $to,
        );

        return vsprintf('#%02x%02x%02x', $channels);
    }
}
