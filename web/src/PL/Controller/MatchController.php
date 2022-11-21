<?php

namespace App\PL\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\BL\Match\MatchManager;
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
}
