<?php

namespace App\Controller;

use App\Clock\Today;
use App\Entity\RecurringTransaction;
use App\Form\Data\RecurringTransactionData;
use App\Form\RecurringTransactionType;
use App\Repository\RecurringTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/recurring')]
final class RecurringController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'recurring_index', methods: ['GET'])]
    public function index(RecurringTransactionRepository $recurring): Response
    {
        return $this->render('recurring/index.html.twig', [
            'recurring' => $recurring->findAllByDescription(),
        ]);
    }

    #[Route('/new', name: 'recurring_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Today $today): Response
    {
        $data = new RecurringTransactionData();
        $data->startDate = $today->date();

        $form = $this->createForm(RecurringTransactionType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($data->toEntity());
            $this->em->flush();

            return $this->redirectToRoute('recurring_index');
        }

        return $this->render('recurring/form.html.twig', ['form' => $form, 'title' => 'Add a recurring transaction']);
    }

    #[Route('/{id}/edit', name: 'recurring_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RecurringTransaction $recurring): Response
    {
        $data = RecurringTransactionData::fromEntity($recurring);
        $form = $this->createForm(RecurringTransactionType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->applyTo($recurring);
            $this->em->flush();

            return $this->redirectToRoute('recurring_index');
        }

        return $this->render('recurring/form.html.twig', ['form' => $form, 'title' => 'Edit '.$recurring->getDescription()]);
    }

    #[Route('/{id}/delete', name: 'recurring_delete', methods: ['POST'])]
    #[IsCsrfTokenValid('submit')]
    public function delete(RecurringTransaction $recurring): Response
    {
        $this->em->remove($recurring);
        $this->em->flush();

        return $this->redirectToRoute('recurring_index');
    }
}
