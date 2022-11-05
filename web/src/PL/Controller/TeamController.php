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
        $table = $dataTable->create()->handleRequest($request);

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

        if ($form->isSubmitted() && $form->isValid()){
            $teamManager->createTeam($team);

            return $this->redirectToRoute('teams');
        }

        return $this->renderForm('team/create.html.twig', ['teamForm' => $form]);
    }

    #[Route('/teams/{id<\d+>}/edit', name: 'team_edit')]
    public function editAction(int $id, Request $request, TeamManager $teamManager): Response
    {
        $team = $teamManager->getTeam($id);
        $form = $this->createForm(TeamEditFormType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $teamManager->updateTeam($team);

            return $this->redirectToRoute('teams');
        }

        return $this->renderForm('team/edit.html.twig', ['teamForm' => $form]);
    }

    #[Route('/teams/{id<\d+>}/people', name: 'get_people')]
    public function getPeopleAjaxAction(int $id, Request $request, TeamManager $teamManager): Response
    {
        $query = $request->get('query');
        return $this->json(['results' => iterator_to_array($teamManager->getPeople($id, $query))]);
    }

    #[Route('/teams/{id<\d+>}/delete', name: 'team_delete')]
    public function deleteAction(int $id, TeamManager $teamManager): Response
    {
        $teamManager->deleteTeam($id);
        return $this->redirectToRoute('teams');
    }

    #[Route('/teams/{id<\d+>}/members', name: 'team_members')]
    public function showMembersAction(int $id, Request $request, TeamManager $teamManager, MembersTable $table): Response
    {
        $form = $this->createForm(TeamAddMemberType::class, options: ['find_url' => ['id' => $id]]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $teamManager->addMembers(explode(' ', $form->get('members')->getData()), $id);
            $this->addFlash('success', 'New members were added');
            return $this->redirectToRoute('team_members', ['id' => $id]);
        }

        return $this->renderForm('team/members.html.twig', [
            'membersForm' => $form,
            'table' => $table->init(['teamId' => $id])
        ]);
    }

    #[Route('/teams/{teamId<\d+>}/members/{memberId<\d+>}', name: 'delete_member')]
    public function deleteMemberAction(int $teamId, int $memberId, TeamManager $teamManager): Response
    {
        $teamManager->deleteMember($teamId, $memberId);
        $this->addFlash('success', 'Member was deleted');
        return $this->redirectToRoute('team_members', ['id' => $teamId]);
    }
}
