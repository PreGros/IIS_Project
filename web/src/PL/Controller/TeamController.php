<?php

namespace App\PL\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\PL\Form\Team\TeamCreateFormType;
use App\BL\Team\TeamManager;
use App\BL\Team\TeamModel;

class TeamController extends AbstractController
{    
    #[Route('/teams/create', name: 'teams_create')]
    public function createAction(Request $request, TeamCreateFormType $form, TeamManager $teamManager): Response
    {
        $team = new TeamModel();
        $form = $this->createForm(TeamCreateFormType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $teamManager->createTeam($team, $form->get('image')->getData());

            return $this->redirectToRoute('datatable');
        }

        return $this->renderForm('team/create.html.twig', ['teamForm' => $form]);
    }
}
