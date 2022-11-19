<?php

namespace App\PL\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\PL\Form\Tournament\TournamentCreateFormType;
use App\BL\Tournament\TournamentManager;
use App\BL\Tournament\TournamentModel;
use App\BL\Tournament\TournamentTypeModel;
use App\DAL\Entity\Tournament;
use App\PL\DataTable\Tournament\TournamentDataTable;
use App\PL\Form\Tournament\TournamentEditFormType;
use App\PL\Form\Tournament\TournamentTypeCreateFormType;
use App\PL\Table\Tournament\TypesTable;

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

            $this->addFlash('success', 'Tournament was added');
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
            'matchingType' => $tournamentModel->getMatchingType(false)->label(),
            'routName' => 'user_info',
            'params' => ['id' => $tournamentModel->getCreatedById()]
        ]);
    }

    #[Route('/tournaments/{id<\d+>}/delete', name: 'tournament_delete')]
    public function deleteAction(int $id, TournamentManager $tournamentManager): Response
    {
        $tournamentManager->deleteTournament($id);
        $this->addFlash('success', 'Tournament was deleted');
        return $this->redirectToRoute('tournaments');
    }

    #[Route('/tournaments/{id<\d+>}/edit', name: 'tournament_edit')]
    public function editAction(int $id, Request $request, TournamentManager $tournamentManager): Response
    {
        $tournament = $tournamentManager->getTournament($id);
        $form = $this->createForm(TournamentEditFormType::class, $tournament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $tournamentManager->updateTournament($tournament);

            $this->addFlash('success', 'Tournament was edited');
            return $this->redirectToRoute('tournaments');
        }

        return $this->renderForm('tournament/edit.html.twig', ['tournamentForm' => $form]);
    }

    #[Route('/tournaments/types', name: 'tournament_types')]
    public function tournamentTypesAction(Request $request, TournamentManager $tournamentManager, TypesTable $typesTable): Response
    {
        $type = new TournamentTypeModel();
        $form = $this->createForm(TournamentTypeCreateFormType::class, $type);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $tournamentManager->createTournamentType($type);

            $this->addFlash('success', 'Tournament type was added');
            return $this->redirectToRoute('tournament_types');
        }

        return $this->renderForm('tournament/types.html.twig', ['tournamentTypeForm' => $form, 'table' => $typesTable->init()]);
    }

    #[Route('/tournaments/types/{id<\d+>}/delete', name: 'tournament_type_delete')]
    public function deleteTypeAction(int $id, TournamentManager $tournamentManager): Response
    {
        $tournamentManager->deleteTournamentType($id);   
        $this->addFlash('success', 'Tournament type was deleted');
        return $this->redirectToRoute('tournament_types');
    }
}
