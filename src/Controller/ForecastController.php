<?php

namespace App\Controller;

use App\Clock\Today;
use App\Forecast\Forecaster;
use App\Forecast\NoStartingBalance;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForecastController extends AbstractController
{
    #[Route('/', name: 'forecast', methods: ['GET'])]
    public function index(Forecaster $forecaster, Today $today): Response
    {
        $today = $today->date();

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
