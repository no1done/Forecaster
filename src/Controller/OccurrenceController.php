<?php

namespace App\Controller;

use App\Entity\RecurringTransaction;
use App\Forecast\Calendar\WorkingDayCalendar;
use App\Form\OccurrenceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapDateTime;
use Symfony\Component\Routing\Attribute\Route;

/**
 * One occurrence of a recurring transaction, identified by the date its rule schedules.
 */
final class OccurrenceController extends AbstractController
{
    #[Route('/recurring/{id}/occurrences/{scheduledDate}', name: 'recurring_occurrence', requirements: ['scheduledDate' => '\d{4}-\d{2}-\d{2}'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        RecurringTransaction $recurring,
        #[MapDateTime(format: '!Y-m-d')] \DateTimeImmutable $scheduledDate,
        WorkingDayCalendar $calendar,
        EntityManagerInterface $em,
    ): Response {
        if (!$recurring->getRule()->isScheduledOn($scheduledDate)) {
            throw $this->createNotFoundException(\sprintf('%s is not scheduled on %s.', $recurring->getDescription(), $scheduledDate->format('Y-m-d')));
        }

        $usual = $recurring->getAmountPence();
        $usualDate = $calendar->onOrBefore($scheduledDate);
        $form = $this->createForm(OccurrenceType::class, [
            'amountPence' => abs($recurring->getAmountPenceFor($scheduledDate)),
            'date' => $recurring->getMovedDateFor($scheduledDate) ?? $usualDate,
        ]);
        $form->handleRequest($request);

        $reset = $form->get('reset');
        \assert($reset instanceof SubmitButton);
        if ($form->isSubmitted() && ($reset->isClicked() || $form->isValid())) {
            $magnitude = $reset->isClicked() ? abs($usual) : $form->get('amountPence')->getData();
            $date = $reset->isClicked() ? $usualDate : $form->get('date')->getData();
            $recurring->overrideAmount($scheduledDate, $usual < 0 ? -$magnitude : $magnitude);
            $recurring->moveOccurrence($scheduledDate, $date == $usualDate ? null : $date);
            $em->flush();

            return $this->redirectToRoute('forecast', ['_fragment' => 'day-'.$date->format('Y-m-d')]);
        }

        return $this->render('occurrence/edit.html.twig', [
            'form' => $form,
            'recurring' => $recurring,
            'scheduledDate' => $scheduledDate,
            'paidOn' => $recurring->getMovedDateFor($scheduledDate) ?? $usualDate,
            'usualDate' => $usualDate,
        ]);
    }
}
