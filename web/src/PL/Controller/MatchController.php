<?php

namespace App\PL\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\BL\Match\MatchManager;
use App\BL\Tournament\TournamentManager;
use App\BL\Tournament\WinCondition;
use App\PL\Form\Match\MatchSetResultFormType;
use App\PL\Table\Match\MatchTable;

class MatchController extends AbstractController
{
    #[Route('/tournaments/{id<\d+>}/matches', name: 'matches')]
    public function getMatchesAction(int $id, MatchManager $matchManager, MatchTable $matchTable, TournamentManager $tournamentManager): Response
    {
        /** @var \App\BL\User\UserModel */
        $user = $this->getUser();
        $tournament = $tournamentManager->getTournament($id);
        $matches = $matchManager->getMatches($tournament);

        $allModifiable = $tournament->getCreatedById() === $user?->getId() || $this->isGranted('ROLE_ADMIN');
        $tables = [];
        foreach ($matches as $matchLevel){
            $tables[] = (clone $matchTable)->init(['matches' => $matchLevel, 'tournamentId' => $tournament->getId(), 'allModifiable' => $allModifiable]);
        }

        return $this->render('match/index.html.twig', ['tables' => $tables]);
    }

    // #[Route('/tournaments/{id<\d+>}/generate-matches/{setParticipants<0|1>}', name: 'generate_matches')]
    // public function generateMatchesAction(int $id, int $setParticipants, MatchManager $matchManager, TournamentManager $tournamentManager): Response
    // {
    //     $tournament = $tournamentManager->getTournament($id);

    //     /** @var \App\BL\User\UserModel */
    //     $user = $this->getUser();

    //     if (!$this->isGranted('ROLE_ADMIN') && $tournament->getCreatedById() !== $user?->getId()){
    //         $this->addFlash('danger', 'Insufficient rights to generate matches');
    //         return $this->redirectToRoute('tournament_info', ['id' => $id]);
    //     }

    //     if (!$tournament->getApproved()){
    //         $this->addFlash('danger', 'Cannot generate matches, tournament is not approved');
    //         return $this->redirectToRoute('tournament_info', ['id' => $id]);
    //     }

    //     $matchManager->generateMatches($tournament, new \DateInterval('PT30M'), new \DateInterval('PT5M'), (bool)$setParticipants);

    //     return $this->redirectToRoute('matches', ['id' => $id]);
    // }
    
    #[Route('/tournaments/{tournamentId<\d+>}/matches/{matchId<\d+>}/edit', name: 'edit_match')]
    public function editMatchAction(int $tournamentId, int $matchId, MatchManager $matchManager, TournamentManager $tournamentManager): Response
    {
        /** @var \App\BL\User\UserModel */
        $user = $this->getUser();
        $tournament = $tournamentManager->getTournament($tournamentId);
        if ($tournament->getCreatedById() !== $user?->getId() && !$this->isGranted('ROLE_ADMIN')){
            $this->addFlash('danger', 'Insufficient rights to edit match');
            return $this->redirectToRoute('matches', ['id' => $tournamentId]);
        }


        return $this->redirectToRoute('matches', ['id' => $tournamentId]);
    }

    #[Route('/tournaments/{tournamentId<\d+>}/matches/{matchId<\d+>}/set-result', name: 'set_match_result')]
    public function setMatchResultAction(int $tournamentId, int $matchId, Request $request, MatchManager $matchManager, TournamentManager $tournamentManager): Response
    {
        /** @var \App\BL\User\UserModel */
        $user = $this->getUser();
        $tournament = $tournamentManager->getTournament($tournamentId);
        if ($tournament->getCreatedById() !== $user?->getId() && !$this->isGranted('ROLE_ADMIN')){
            $this->addFlash('danger', 'Insufficient rights to set result to the match');
            return $this->redirectToRoute('matches', ['id' => $tournamentId]);
        }

        $usePoints = $tournament->getWinCondition(false) === WinCondition::MaxPoints || $tournament->getWinCondition(false) === WinCondition::MinPoints;
        $match = $matchManager->getMatch($matchId);
        $form = $this->createForm(MatchSetResultFormType::class, $match, ['match' => $match, 'use_points' => $usePoints]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $matchManager->setMatchResult($match, $tournament);
            $this->addFlash('success', 'Result was set');
            return $this->redirectToRoute('matches', ['id' => $tournamentId]);
        }

        return $this->renderForm('match/set_result.html.twig', ['form' => $form, 'use_points' => $usePoints]);
    }
}
