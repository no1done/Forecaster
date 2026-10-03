<?php

namespace App\Controller;

use App\Clock\Today;
use App\Entity\BalanceCheckpoint;
use App\Form\CheckpointType;
use App\Form\Data\CheckpointData;
use App\Repository\BalanceCheckpointRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/balances')]
final class BalanceController extends AbstractController
{
    public function __construct(
        private readonly BalanceCheckpointRepository $checkpoints,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'balance_index', methods: ['GET', 'POST'])]
    public function index(Request $request, Today $today): Response
    {
        $data = new CheckpointData(date: $today->date());
        $form = $this->createForm(CheckpointType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // One checkpoint per day: recording a date again corrects it.
            $checkpoint = $this->checkpoints->findOneOnDate($data->date);
            if (null === $checkpoint) {
                $this->em->persist(new BalanceCheckpoint($data->date, $data->balancePence));
            } else {
                $checkpoint->setBalancePence($data->balancePence);
            }
            $this->em->flush();

            return $this->redirectToRoute('balance_index');
        }

        return $this->render('balance/index.html.twig', [
            'form' => $form,
            'checkpoints' => $this->checkpoints->findNewestFirst(),
        ]);
    }

    #[Route('/{id}/delete', name: 'balance_delete', methods: ['POST'])]
    #[IsCsrfTokenValid('submit')]
    public function delete(BalanceCheckpoint $checkpoint): Response
    {
        $this->em->remove($checkpoint);
        $this->em->flush();

        return $this->redirectToRoute('balance_index');
    }
}
