<?php

namespace App\Controller;

use App\Clock\Today;
use App\Forecast\ForecastDay;
use App\Forecast\Forecaster;
use App\Form\Data\ForecastFilter;
use App\Form\ForecastFilterType;
use App\Repository\BalanceCheckpointRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForecastController extends AbstractController
{
    #[Route('/', name: 'forecast', methods: ['GET'])]
    public function index(Request $request, Forecaster $forecaster, BalanceCheckpointRepository $checkpoints, Today $today): Response
    {
        $today = $today->date();
        $earliest = $checkpoints->findEarliest()?->getDate();
        if (null === $earliest) {
            return $this->render('forecast/index.html.twig', ['days' => null, 'today' => $today]);
        }

        $defaultFrom = max($today, $earliest);
        $default = new ForecastFilter($defaultFrom, $defaultFrom->modify('+1 year -1 day'));
        $filter = clone $default;
        $form = $this->createForm(ForecastFilterType::class, $filter, [
            'earliest' => $earliest,
            'default_from' => $defaultFrom,
        ]);
        $form->handleRequest($request);

        $range = $form->isSubmitted() && $form->isValid() ? $filter : $default;
        $days = $forecaster->forecast($range->from, $range->to);

        $search = trim($filter->search ?? '');
        if ('' !== $search) {
            $days = array_values(array_filter($days, static fn (ForecastDay $day) => $day->matches($search)));
        }

        return $this->render('forecast/index.html.twig', [
            'days' => $days,
            'today' => $today,
            'from' => $range->from,
            'to' => $range->to,
            'search' => $search,
            'form' => $form,
        ]);
    }
}
