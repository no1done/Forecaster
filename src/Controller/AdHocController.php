<?php

namespace App\Controller;

use App\Clock\Today;
use App\Entity\AdHocTransaction;
use App\Form\AdHocTransactionType;
use App\Form\Data\AdHocTransactionData;
use App\Repository\AdHocTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/ad-hoc')]
final class AdHocController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'ad_hoc_index', methods: ['GET'])]
    public function index(AdHocTransactionRepository $transactions, Today $today): Response
    {
        return $this->render('ad_hoc/index.html.twig', [
            'upcoming' => $transactions->findOnOrAfter($today->date()),
            'past' => $transactions->findRecentBefore($today->date(), 20),
        ]);
    }

    #[Route('/new', name: 'ad_hoc_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Today $today,
        #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => '/^\d{4}-\d{2}-\d{2}$/'])] ?string $date = null,
    ): Response {
        $data = new AdHocTransactionData();
        $data->date = null !== $date ? new \DateTimeImmutable($date) : $today->date();

        $form = $this->createForm(AdHocTransactionType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($data->toEntity());
            $this->em->flush();

            return $this->redirectToDay($data->date);
        }

        return $this->render('ad_hoc/form.html.twig', ['form' => $form, 'title' => 'Add an ad-hoc transaction']);
    }

    #[Route('/{id}/edit', name: 'ad_hoc_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AdHocTransaction $transaction): Response
    {
        $data = AdHocTransactionData::fromEntity($transaction);
        $form = $this->createForm(AdHocTransactionType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->applyTo($transaction);
            $this->em->flush();

            return $this->redirectToDay($data->date);
        }

        return $this->render('ad_hoc/form.html.twig', ['form' => $form, 'title' => 'Edit '.$transaction->getDescription(), 'transaction' => $transaction]);
    }

    #[Route('/{id}/delete', name: 'ad_hoc_delete', methods: ['POST'])]
    #[IsCsrfTokenValid('submit')]
    public function delete(AdHocTransaction $transaction): Response
    {
        $this->em->remove($transaction);
        $this->em->flush();

        return $this->redirectToRoute('ad_hoc_index');
    }

    private function redirectToDay(\DateTimeImmutable $date): Response
    {
        return $this->redirectToRoute('forecast', ['_fragment' => 'day-'.$date->format('Y-m-d')]);
    }
}
