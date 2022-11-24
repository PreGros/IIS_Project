<?php

namespace App\PL\Controller;

use App\BL\Match\MatchManager;
use App\BL\Team\TeamManager;
use App\BL\Tournament\ParticipantType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\PL\Form\Tournament\TournamentCreateFormType;
use App\BL\Tournament\TournamentManager;
use App\BL\Tournament\TournamentModel;
use App\BL\Tournament\TournamentTypeModel;
use App\BL\Util\DateTimeUtil;
use App\PL\DataTable\Tournament\TournamentDataTable;
use App\PL\DataTable\Tournament\TournamentParticipantDataTable;
use App\PL\Form\Tournament\TournamentEditFormType;
use App\PL\Form\Tournament\TournamentMatchGenerationFormType;
use App\PL\Form\Tournament\TournamentRegistrationFormType;
use App\PL\Form\Tournament\TournamentTypeCreateFormType;
use App\PL\Form\Tournament\TournamentTypeEditFormType;
use App\PL\Table\Tournament\TypesTable;

class TournamentController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    #[Route('/tournaments', name: 'tournaments')]
    public function tournamentsAction(Request $request, TournamentDataTable $dataTable): Response
    {
        $table = $dataTable->create($this->isGranted('ROLE_ADMIN'))->handleRequest($request);

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
        $errMessage = "";

        if ($form->isSubmitted() && $form->isValid()){
            if ($tournamentManager->checkOnCreateValidity($tournament, $errMessage)) {
                $tournamentManager->createTournament($tournament);

                $this->addFlash('success', 'Tournament was added');
                return $this->redirectToRoute('tournaments');
            }
            else{
                $this->addFlash('danger', $errMessage);
            }
        }

        return $this->renderForm('tournament/create.html.twig', ['tournamentForm' => $form]);
    }

    #[Route('/tournaments/{id<\d+>}', name: 'tournament_info')]
    public function getTournamentInfo(int $id, Request $request, TournamentManager $tournamentManager, TeamManager $teamManager,  MatchManager $matchManager, TournamentParticipantDataTable $dataTable): Response
    {
        $tournamentModel = $tournamentManager->getTournament($id);

        /** @var \App\BL\User\UserModel */
        $user = $this->getUser();

        $participantName = null;
        $participantId = null;
        $isLeader = null;
        
        if($tournamentModel->getCurrentUserRegistrationState() !== null){
            // registered
            if($tournamentModel->getParticipantType(false) == ParticipantType::Teams){
                // registered as team
                $ret = $teamManager->getRegisteredTeamParticipant($id, $user->getId());
                $isLeader = $ret[0];
                $participantId = $ret[1];
                $participantName = $ret[2];
            }
            else{
                // registered as user
                $participantName = $user->getNickname();
                $participantId = $user->getId();
            }
        }

        
        // REGISTER TEAM
        $form = $this->createForm(TournamentRegistrationFormType::class, options: [
            'teams' => $teamManager->getFormatedUserTeams($user?->getId())
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $tournamentManager->addTournamentParticipantTeam($id, $form->get('teams')->getData());

            $this->addFlash('success', 'Your team was successfully registered in this tournament. Now wait for approval');
            return $this->redirectToRoute('tournament_info', ['id' => $id]);
        }


        // GENERATE MATCHES
        $matchForm = $this->createForm(TournamentMatchGenerationFormType::class, options: [
            'disabled' => !$tournamentModel->getApproved(),
            'titleDisabled' => $tournamentModel->getApproved() ? "" : "This tournament is not approved"
        ]);
        $matchForm->handleRequest($request);

        if ($matchForm->isSubmitted() && $matchForm->isValid()){
            //$matchManager->generateMatches($tournamentModel, new \DateInterval('PT30M'), new \DateInterval('PT5M'), (bool)$setParticipants);
            $matchManager->generateMatches($tournamentModel, $matchForm->get('duration')->getData(), $matchForm->get('break')->getData(), $matchForm->get('setParticipants')->getData());
            return $this->redirectToRoute('matches', ['id' => $id]);
        }


        $table = $dataTable->create($id, $this->isGranted('ROLE_ADMIN'))->handleRequest($request);

        if ($table->isCallback()){
            return $table->getResponse();
        }

        return $this->renderForm('tournament/info.html.twig', [
            'form' => $form,
            'matchForm' => $matchForm,
            'datatable' => $table,
            'tournament' => $tournamentModel,
            'participantType' => $tournamentModel->getParticipantType(false)->label(),
            'date' => $tournamentModel->getDate()->format('j. n. Y G:i'),
            'registrationDateStart' => $tournamentModel->getRegistrationDateStart()->format('j. n. Y G:i'),
            'registrationDateEnd' => $tournamentModel->getRegistrationDateEnd()->format('j. n. Y G:i'),
            'winCondition' => $tournamentModel->getWinCondition(false)->label(),
            'matchingType' => $tournamentModel->getMatchingType(false)->label(),
            'routName' => 'user_info',
            'params' => ['id' => $tournamentModel->getCreatedById()],
            'showRegistrate' => (($tournamentModel->canRegistrate()) || ($tournamentModel->getCurrentUserRegistrationState() !== null)) && $user !== null,
            'participantIsTeam' => ($tournamentModel->getParticipantType(false) == ParticipantType::Teams),
            'redirectParam' => ['id' => $id],
            'isRegistered' => $tournamentModel->getCurrentUserRegistrationState() !== null,
            'participantId' => $participantId,
            'participantName' => $participantName,
            'canUnregister' => (($isLeader !== false) && $tournamentModel->canRegistrate() ),
            'registrationEnded' => $tournamentModel->registrationEnded(),
            'matchesGenerated' => $tournamentManager->areTournamentMatchesGenerated($id),
            'canGenerateMatches' => ($tournamentModel->getCreatedById() === $user?->getId() || $this->isGranted('ROLE_ADMIN'))
        ]);
    }

    #[Route('/tournaments/{id<\d+>}/register', name: 'tournament_register')]
    public function registerAction(int $id, TournamentManager $tournamentManager): Response
    {
        $tournamentManager->addTournamentParticipantCurrUser($id);
        $this->addFlash('success', 'You were successfully registered in this tournament. Now wait for your approval');
        return $this->redirectToRoute('tournament_info', ['id' => $id]);
    }

    #[Route('/tournaments/{id<\d+>}/unregister', name: 'tournament_unregister')]
    public function unregisterAction(int $id, TournamentManager $tournamentManager): Response
    {
        /** @var \App\BL\User\UserModel */
        $currUser = $this->getUser();
        $tournamentManager->removeTournamentParticipant($id, $currUser->getId());
        // $this->addFlash('success', 'You were successfully unregistered from this tournament');
        return $this->redirectToRoute('tournament_info', ['id' => $id]);
    }

    #[Route('/tournaments/{id<\d+>}/delete', name: 'tournament_delete')]
    public function deleteAction(int $id, TournamentManager $tournamentManager): Response
    {
        $tournament = $tournamentManager->getTournament($id);
        /** @var \App\BL\User\UserModel */
        $user = $this->getUser();

        if ($user?->getId() === null || (!$this->isGranted('ROLE_ADMIN') && $tournament?->getCreatedById() !== $user?->getId())){
            $this->addFlash('danger', 'Insufficient rights to delete tournament');
            return $this->redirectToRoute('tournaments');
        }

        $tournamentManager->deleteTournament($id);
        $this->addFlash('success', 'Tournament was deleted');
        return $this->redirectToRoute('tournaments');
    }

    #[Route('/tournaments/{id<\d+>}/edit', name: 'tournament_edit')]
    public function editAction(int $id, Request $request, TournamentManager $tournamentManager): Response
    {
        $tournament = $tournamentManager->getTournament($id);/** @var \App\BL\User\UserModel */
        $user = $this->getUser();

        if ($user?->getId() === null || (!$this->isGranted('ROLE_ADMIN') && $tournament?->getCreatedById() !== $user?->getId())){
            $this->addFlash('danger', 'Insufficient rights to edit tournament');
            return $this->redirectToRoute('tournaments');
        }

        if ($tournament->getApproved())
        {
            $this->addFlash('danger', 'Approved tournaments cannot be edited');
            return $this->redirectToRoute('tournaments');
        }

        $form = $this->createForm(TournamentEditFormType::class, $tournament);
        $form->handleRequest($request);
        $errMessage = "";

        if ($form->isSubmitted() && $form->isValid()){
            if ($tournamentManager->checkOnCreateValidity($tournament, $errMessage)) {
                $tournamentManager->updateTournament($tournament);

                $this->addFlash('success', 'Tournament was edited');
                return $this->redirectToRoute('tournaments');
            }
            else{
                $this->addFlash('danger', $errMessage);
            }
        }

        return $this->renderForm('tournament/edit.html.twig', ['tournamentForm' => $form]);
    }

    #[Route('/tournaments/types', name: 'tournament_types')]
    public function tournamentTypesAction(Request $request, TournamentManager $tournamentManager, TypesTable $typesTable): Response
    {
        $type = new TournamentTypeModel();
        $form = $this->createForm(TournamentTypeCreateFormType::class, $type);
        $form->handleRequest($request);
        $errMessage = "";

        if ($form->isSubmitted() && $form->isValid()){
            if ($tournamentManager->checkOnCreateTypeValidity($type, $errMessage)){
                $tournamentManager->createTournamentType($type);

                $this->addFlash('success', 'Tournament type was added');
                return $this->redirectToRoute('tournament_types');
            }
            $this->addFlash('danger', $errMessage);
        }

        return $this->renderForm('tournament/types.html.twig', ['tournamentTypeForm' => $form, 'table' => $typesTable->init()]);
    }

    #[Route('/tournaments/types/{id<\d+>}/edit', name: 'tournament_type_edit')]
    public function editTypeAction(int $id, TournamentManager $tournamentManager, Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')){
            $this->addFlash('danger', 'Insufficient rights to edit team');
            return $this->redirectToRoute('teams');
        }

        $type = $tournamentManager->getTournamentType($id);
        $form = $this->createForm(TournamentTypeEditFormType::class, $type);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $tournamentManager->updateType($type);  

            $this->addFlash('success', 'Successfuly edited');
            return $this->redirectToRoute('tournament_types');
        }

        return $this->renderForm('tournament/editType.html.twig', ['formEditType' => $form]);
    }

    #[Route('/tournaments/{id<\d+>}/approve', name: 'user_approve')]
    public function approveTournament(int $id, TournamentManager $tournamentManager): Response
    {
        $tournamentManager->approveTournament($id);
        return $this->redirectToRoute('tournaments');
    }

    #[Route('/tournaments/{id<\d+>}/disapprove', name: 'user_disapprove')]
    public function disapproveTournament(int $id, TournamentManager $tournamentManager): Response
    {
        $tournamentManager->disapproveTournament($id);
        return $this->redirectToRoute('tournaments');
    }

    #[Route('/tournaments/{tId<\d+>}/participants/{pId<\d+>}/approve', name: 'participant_approve')]
    public function approveParticipant(int $tId, int $pId, TournamentManager $tournamentManager): Response
    {
        $tournamentManager->approveParticipant($pId);
        return $this->redirectToRoute('tournament_info', ['id' => $tId]);
    }

    #[Route('/tournaments/{tId<\d+>}/participants/{pId<\d+>}/disapprove', name: 'participant_disapprove')]
    public function disapproveParticipant(int $tId, int $pId, TournamentManager $tournamentManager): Response
    {
        $tournamentManager->disapproveParticipant($pId);
        return $this->redirectToRoute('tournament_info', ['id' => $tId]);
    }
}
