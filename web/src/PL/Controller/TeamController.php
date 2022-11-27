<?php

namespace App\PL\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\PL\Form\Team\TeamCreateFormType;
use App\BL\Team\TeamManager;
use App\BL\Team\TeamModel;
use App\PL\Form\Team\TeamAddMemberType;
use App\PL\DataTable\Team\TeamDataTable;
use App\PL\Form\Team\TeamEditFormType;
use App\PL\Table\Team\MembersTable;

class TeamController extends AbstractController
{
    #[Route('/teams', name: 'teams')]
    public function teamsAction(Request $request, TeamDataTable $dataTable): Response
    {
        $table = $dataTable->create($this->isGranted('ROLE_ADMIN'))->handleRequest($request);

        if ($table->isCallback()){
            return $table->getResponse();
        }

        return $this->render('team/index.html.twig', ['datatable' => $table]);
    }

    #[Route('/teams/create', name: 'team_create')]
    public function createAction(Request $request, TeamManager $teamManager): Response
    {
        $team = new TeamModel();
        $form = $this->createForm(TeamCreateFormType::class, $team);
        $form->handleRequest($request);
        $errMessage = "";

        if ($form->isSubmitted() && $form->isValid()){
            if ($teamManager->checkOnCreateValidity($team, $errMessage)){
                $teamManager->createTeam($team);

                $this->addFlash('success', 'Team successfuly created!');
                return $this->redirectToRoute('teams');
            }
            $this->addFlash('danger', $errMessage);
        }

        return $this->renderForm('team/create.html.twig', ['teamForm' => $form]);
    }

    #[Route('/teams/{id<\d+>}/edit', name: 'team_edit')]
    public function editAction(int $id, Request $request, TeamManager $teamManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$teamManager->isCurrentUserLeader($id)){
            $this->addFlash('danger', 'Insufficient rights to edit team');
            return $this->redirectToRoute('teams');
        }

        $team = $teamManager->getTeam($id);

        if ($team->getIsDeactivated()){
            $this->addFlash('danger', 'Cannot edit - team was deactivated!');
            return $this->redirectToRoute('teams');
        }

        $form = $this->createForm(TeamEditFormType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $teamManager->updateTeam($team);

            return $this->redirectToRoute('teams');
        }

        return $this->renderForm('team/edit.html.twig', ['teamForm' => $form, 'team' => $team]);
    }

    #[Route('/teams/{id<\d+>}/people', name: 'get_people')]
    public function getPeopleAjaxAction(int $id, Request $request, TeamManager $teamManager): Response
    {
        $query = $request->get('query');
        return $this->json(['results' => iterator_to_array($teamManager->getPeople($id, $query))]);
    }

    #[Route('/teams/{id<\d+>}/deactivate', name: 'team_deactivate')]
    public function deactivateAction(int $id, TeamManager $teamManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$teamManager->isCurrentUserLeader($id)){
            $this->addFlash('danger', 'Insufficient rights to deactivate team');
            return $this->redirectToRoute('teams');
        }

        $this->addFlash('warning', 'Team deactivated');
        $teamManager->deactivateTeam($id);
        return $this->redirectToRoute('teams');
    }

    #[Route('/teams/{teamId<\d+>}/members/{memberId<\d+>}/delete', name: 'delete_member')]
    public function deleteMemberAction(int $teamId, int $memberId, TeamManager $teamManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$teamManager->isCurrentUserLeader($teamId)){
            $this->addFlash('danger', 'Insufficient rights to delete team member');
            return $this->redirectToRoute('teams');
        }

        $teamManager->deleteMember($teamId, $memberId);
        $this->addFlash('success', 'Member was deleted');
        return $this->redirectToRoute('team_info', ['id' => $teamId]);
    }

    #[Route('/teams/{id<\d+>}/info', name: 'team_info')]
    public function teamInfoAction(int $id, Request $request, TeamManager $teamManager, MembersTable $table): Response
    {
        $canModify = $this->isGranted('ROLE_ADMIN') || $teamManager->isCurrentUserLeader($id);

        $form = $this->createForm(TeamAddMemberType::class, options: ['find_url' => ['id' => $id]]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $teamManager->addMembers(explode(' ', $form->get('members')->getData()), $id);
            $this->addFlash('success', 'New members were added');
            return $this->redirectToRoute('team_info', ['id' => $id]);
        }

        $teamModel = $teamManager->getTeam($id);
        return $this->renderForm('team/info.html.twig', [
            'id' => $id,
            'team' => $teamModel,
            'membersForm' => $form,
            'canModify' => $canModify,
            'deactivated' => $teamModel->getIsDeactivated(),
            'statistics' => $teamManager->getTeamStatistics($id),
            'table' => $table->init(['teamId' => $id, 'canModify' => $canModify])
        ]);
    }
}
