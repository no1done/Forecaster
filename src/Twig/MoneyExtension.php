<?php

namespace App\Twig;

use App\Forecast\BalanceColour;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final class MoneyExtension
{
    public function __construct(
        private readonly BalanceColour $balanceColour,
    ) {
    }

    #[AsTwigFilter('pence')]
    public static function formatPence(int $pence): string
    {
        $absolute = abs($pence);

        return \sprintf('%s£%s.%02d', $pence < 0 ? '-' : '', number_format(intdiv($absolute, 100)), $absolute % 100);
    }

    #[AsTwigFunction('balance_colour')]
    public function balanceColour(int $balancePence): string
    {
        return $this->balanceColour->forBalance($balancePence);
    }
}
