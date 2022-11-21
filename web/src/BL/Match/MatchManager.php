<?php

namespace App\BL\Match;

use App\BL\Tournament\MatchingType;
use App\BL\Tournament\TournamentManager;
use App\BL\Tournament\TournamentModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use App\BL\Util\AutoMapper;

class MatchManager
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private MatchGenerator $matchGenerator;
    private TournamentManager $tournamentManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        //MatchGenerator $matchGenerator,
        TournamentManager $tournamentManager
    )
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        //$this->matchGenerator = $matchGenerator;
        $this->tournamentManager = $tournamentManager;
    }

    /**
     * @param array<MatchModel> $matches
     * @param array<array<MatchModel>>
     */
    private function convertTreeToLayers(array $matches): array
    {
        $layers = [];
        $layer = 0;
        
        foreach ($matches as $node){
            if (in_array($node->getId(), isset($layers[$layer]) ? array_map(fn (MatchModel $m) => $m->getChildId(), $layers[$layer]) : [])){
                if ($layer !== 0){
                    $children = array_map(fn (MatchModel $m) => $m->getChildId(), $layers[$layer - 1]);
                    usort($layers[$layer], function (MatchModel $a, MatchModel $b) use ($children){
                        return
                            (($val = array_search($a->getId(), $children)) === false ? PHP_INT_MAX : $val)
                                <=>
                            (($val = array_search($b->getId(), $children)) === false ? PHP_INT_MAX : $val);
                    });
                }
                $layer++;
            }
            $layers[$layer][] = $node;
        }

        return $layers;
    }

    public function getMatches(int $id)
    {
        /** @var \App\DAL\Repository\TournamentRepository */
        $tournamentRepo = $this->entityManager->getRepository(\App\DAL\Entity\Tournament::class);
        /** @var TournamentModel */
        $tournament = AutoMapper::map($tournamentRepo->find($id), TournamentModel::class, trackEntity: false);

        /** @var \App\DAL\Repository\TournamentMatchRepository */
        $matchesRepo = $this->entityManager->getRepository(\App\DAL\Entity\TournamentMatch::class);
        $matcheModels = $matchesRepo->findBy(['tournament' => $id]);
        $matches = [];

        foreach ($matcheModels as $matchModel){
            /** @var MatchModel */
            $match = AutoMapper::map($matchModel, MatchModel::class, trackEntity: false);
            $match->setChildId($matchModel->getChildMatch()?->getId());
            $matches[] = $match;
        }

        if ($tournament->getMatchingType(false) === MatchingType::Elimination){
            return $this->convertTreeToLayers($matches);
        }

        return [$matches];
    }

    public function generateMatches(int $tournamentId, bool $setParticipantsToMatches = true)
    {
        $tournament = $this->tournamentManager->getTournament($tournamentId);

        if (!$tournament->getApproved()){
            return;
        }

        /** @var \App\DAL\Repository\TournamentParticipantRepository */
        $participantsRepo = $this->entityManager->getRepository(\App\DAL\Entity\TournamentParticipant::class);
        $participants = $participantsRepo->findBy(['tournament' => $tournamentId, 'approved' => true]);
        
        $this->matchGenerator->init($participants, $tournament, $setParticipantsToMatches);
        if ($tournament->getMatchingType(false) === MatchingType::Elimination){
            $this->matchGenerator->generateMatchesSingleElimination();
            return;
        }

        $this->matchGenerator->generateMatchesRoundRobin();
    }
}
