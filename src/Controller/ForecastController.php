<?php

namespace App\Controller;

use App\Forecast\Forecaster;
use App\Forecast\NoStartingBalance;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForecastController extends AbstractController
{
    private const string TIMEZONE = 'Europe/London';

    #[Route('/', name: 'forecast', methods: ['GET'])]
    public function index(Forecaster $forecaster, ClockInterface $clock): Response
    {
        $today = new \DateTimeImmutable($clock->now()->setTimezone(new \DateTimeZone(self::TIMEZONE))->format('Y-m-d'));

        try {
            $days = $forecaster->forecast($today, $today->modify('+1 year -1 day'));
        } catch (NoStartingBalance) {
            $days = null;
        }

        return $this->render('forecast/index.html.twig', [
            'days' => $days,
            'today' => $today,
        ]);
    }
}
