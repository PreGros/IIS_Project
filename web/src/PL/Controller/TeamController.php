<?php

namespace App\PL\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\PL\Form\Team\TeamCreateFormType;
use App\BL\Team\TeamManager;
use App\BL\Team\TeamModel;
use App\PL\DataTable\Team\TeamDataTable;

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
    public function createAction(Request $request, TeamCreateFormType $form, TeamManager $teamManager): Response
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

    #[Route('/teams/edit/{id<\d+>}', name: 'team_edit')]
    public function editAction(int $id, Request $request, TeamCreateFormType $form, TeamManager $teamManager): Response
    {
        $team = $teamManager->getTeam($id);
        $form = $this->createForm(TeamCreateFormType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $teamManager->updateTeam($team);

            return $this->redirectToRoute('teams');
        }

        return $this->renderForm('team/edit.html.twig', ['teamForm' => $form]);
    }
}
