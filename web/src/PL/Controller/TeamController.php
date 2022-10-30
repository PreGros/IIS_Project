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
use App\PL\Form\Team\TeamEditFormType;

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

    #[Route('/teams/edit/{id<\d+>}', name: 'team_edit')]
    public function editAction(int $id, Request $request, TeamManager $teamManager): Response
    {
        $team = $teamManager->getTeam($id);
        $form = $this->createForm(TeamEditFormType::class, $team, ['find_url' => ['id' => $id]]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $teamManager->updateTeam($team);

            return $this->redirectToRoute('teams');
        }

        return $this->renderForm('team/edit.html.twig', ['teamForm' => $form]);
    }

    #[Route('/teams/people/{id<\d+>}', name: 'get_people')]
    public function getPeopleAjaxAction(int $id, Request $request, TeamManager $teamManager): Response
    {
        $query = $request->get('query');
        $teamManager->getPeople($id, $query);
        if ($query === "admin"){ 
            return $this->json(['results' => [['value' => '1', 'text' => 'admin'], ['value' => '2', 'text' => 'alsoAdmin']]]);
        }
        return $this->json(['results' => []]);
    }
}
