<?php

namespace App\PL\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\BL\Match\MatchManager;
use App\BL\Tournament\TournamentManager;
use App\PL\Table\Match\MatchTable;

class MatchController extends AbstractController
{
    #[Route('/tournaments/{id<\d+>}/matches', name: 'matches')]
    public function getMatches(int $id, MatchManager $matchManager, MatchTable $matchTable): Response
    {
        $matches = $matchManager->getMatches($id);

        $tables = [];
        foreach ($matches as $matchLevel){
            $tables[] = (clone $matchTable)->init(['matches' => $matchLevel, 'canModify' => false]);
        }

        return $this->render('match/index.html.twig', ['tables' => $tables]);
    }

    #[Route('/tournaments/{id<\d+>}/generate-matches/{setParticipants<0|1>}', name: 'generate_matches')]
    public function generateMatches(int $id, int $setParticipants, MatchManager $matchManager, TournamentManager $tournamentManager): Response
    {
        $tournament = $tournamentManager->getTournament($id);

        /** @var \App\BL\User\UserModel */
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN') && $tournament->getCreatedById() !== $user?->getId()){
            $this->addFlash('danger', 'Insufficient rights to generate matches');
            return $this->redirectToRoute('tournament_info', ['id' => $id]);
        }

        if (!$tournament->getApproved()){
            $this->addFlash('danger', 'Cannot generate matches, tournament is not approved');
            return $this->redirectToRoute('tournament_info', ['id' => $id]);
        }

        $matchManager->generateMatches($tournament, (bool)$setParticipants);

        return $this->redirectToRoute('matches', ['id' => $id]);
    }
}
