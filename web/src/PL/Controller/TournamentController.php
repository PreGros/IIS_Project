<?php

namespace App\PL\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\PL\Form\Tournament\TournamentCreateFormType;
use App\BL\Tournament\TournamentManager;
use App\BL\Tournament\TournamentModel;
use App\PL\DataTable\Tournament\TournamentDataTable;

class TournamentController extends AbstractController
{
    #[Route('/tournaments', name: 'tournaments')]
    public function tournamentsAction(Request $request, TournamentDataTable $dataTable): Response
    {
        $table = $dataTable->create()->handleRequest($request);

        if ($table->isCallback()){
            return $table->getResponse();
        }

        return $this->render('tournament/index.html.twig', ['datatable' => $table]);
    }

    #[Route('/tournaments/create', name: 'tournament_create')]
    public function createAction(Request $request, TournamentManager $tournamentManager): Response
    {
        $tournament = new TournamentModel();
        $form = $this->createForm(TournamentCreateFormType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $tournamentManager->createTournament($tournament);

            return $this->redirectToRoute('tournaments');
        }

        return $this->renderForm('tournament/create.html.twig', ['tournamentForm' => $form]);
    }

    #[Route('/tournaments/{id<\d+>}', name: 'tournament_info')]
    public function getTournamentInfo(int $id, TournamentManager $tournamentManager): Response
    {
        $tournamentModel = $tournamentManager->getTournament($id);
        return $this->render('tournament/info.html.twig', [
            'tournament' => $tournamentModel,
            'participantType' => $tournamentModel->getParticipantType(false)->label(),
            'date' => $tournamentModel->getDate()->format('j. n. Y G:i'),
            'registrationDateStart' => $tournamentModel->getRegistrationDateStart()->format('j. n. Y G:i'),
            'registrationDateEnd' => $tournamentModel->getRegistrationDateEnd()->format('j. n. Y G:i'),
            'winCondition' => $tournamentModel->getWinCondition(false)->label(),
            'matchingType' => $tournamentModel->getMatchingType(false)->label()
        ]);
    }
}