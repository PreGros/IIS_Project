<?php

namespace App\BL\Match;

use App\BL\Team\TeamModel;
use App\BL\Tournament\MatchingType;
use App\BL\Tournament\TournamentModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use App\BL\Util\AutoMapper;
use App\DAL\Entity\Tournament;
use App\BL\Tournament\TournamentManager;
use App\BL\Tournament\WinCondition;
use App\BL\User\UserModel;
use App\DAL\Entity\MatchParticipant;
use App\DAL\Entity\TournamentMatch;

class MatchManager
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private MatchGenerator $matchGenerator;
    private TournamentManager $tournamentManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        MatchGenerator $matchGenerator,
        TournamentManager $tournamentManager
    )
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->matchGenerator = $matchGenerator;
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

    public function getMatches(TournamentModel $tournament)
    {
        /** @var \App\DAL\Repository\TournamentMatchRepository */
        $matchesRepo = $this->entityManager->getRepository(\App\DAL\Entity\TournamentMatch::class);
        $entities = $matchesRepo->findAllWithParticipants($tournament->getId());
        /** @var array<MatchModel> */
        $matches = [];
        $matchIndex = -1;
        $matchParticipantCount = 0;
        for ($i = 0; $i < count($entities); $i++){
            if ($entities[$i] instanceof \App\DAL\Entity\TournamentMatch){
                /** @var MatchModel $match */
                $match = AutoMapper::map($entities[$i], MatchModel::class, trackEntity: false);
                $match->setChildId($entities[$i]->getChildMatch()?->getId());
                $matches[++$matchIndex] = $match;
                $matchParticipantCount = 0;
                continue;
            }

            if ($entities[$i] instanceof \App\DAL\Entity\MatchParticipant){
                if ($matchParticipantCount === 0){
                    $matches[$matchIndex]->setParticipant1($this->mapMatchParticipant($entities[$i]));
                }
                elseif ($matchParticipantCount === 1){
                    $matches[$matchIndex]->setParticipant2($this->mapMatchParticipant($entities[$i]));
                }
                $matchParticipantCount++;
            }
        }

        if ($tournament->getMatchingType(false) === MatchingType::Elimination){
            return $this->convertTreeToLayers($matches);
        }

        return [$matches];
    }

    private function mapMatchParticipant(\App\DAL\Entity\MatchParticipant $matchParticipant, bool $trackParticipant = false): MatchParticipantModel
    {
        /** @var MatchParticipantModel */
        $matchP = AutoMapper::map($matchParticipant, MatchParticipantModel::class, trackEntity: $trackParticipant);
        $tournamentP = $matchParticipant->getTournamentParticipant();
        $team = $tournamentP?->getSignedUpTeam() !== null ? AutoMapper::map($tournamentP->getSignedUpTeam(), TeamModel::class, trackEntity: false) : null;
        $user = $tournamentP?->getSignedUpUser() !== null ? AutoMapper::map($tournamentP->getSignedUpUser(), UserModel::class, trackEntity: false) : null;
        return $matchP->setParticipant($team ?? $user ?? null);
    }

    public function generateMatches(TournamentModel $tournament, bool $setParticipantsToMatches = true)
    {
        /** @var \App\DAL\Repository\TournamentParticipantRepository */
        $participantsRepo = $this->entityManager->getRepository(\App\DAL\Entity\TournamentParticipant::class);
        $participants = $participantsRepo->findBy(['tournament' => AutoMapper::map($tournament, Tournament::class, trackEntity: false), 'approved' => true]);

        $this->matchGenerator->init($participants, $tournament, $setParticipantsToMatches);
        if ($tournament->getMatchingType(false) === MatchingType::Elimination){
            $this->matchGenerator->generateMatchesSingleElimination();
            return;
        }

        $this->matchGenerator->generateMatchesRoundRobin();
    }

    public function getMatch(int $id): MatchModel
    {
        /** @var \App\DAL\Repository\TournamentMatchRepository */
        $matchesRepo = $this->entityManager->getRepository(\App\DAL\Entity\TournamentMatch::class);
        $entities = $matchesRepo->findWithParticipants($id);

        /** @var MatchModel $match */
        $match = AutoMapper::map($entities[0], MatchModel::class);
        $match->setChildId($entities[0]->getChildMatch()?->getId());
        
        $matchParticipantCount = 0;
        foreach ($entities as $entity){
            if ($entity instanceof \App\DAL\Entity\MatchParticipant){
                if ($matchParticipantCount === 0){
                    $match->setParticipant1($this->mapMatchParticipant($entity, true));
                }
                elseif ($matchParticipantCount === 1){
                    $match->setParticipant2($this->mapMatchParticipant($entity, true));
                }
                $matchParticipantCount++;
            }
        }

        return $match;
    }

    public function setMatchResult(MatchModel $match, TournamentModel $tournament)
    {
        $participant1 = $match->getParticipant1() !== null ? AutoMapper::map($match->getParticipant1(), MatchParticipant::class, trackEntity: false) : null;
        $participant2 = $match->getParticipant2() !== null ? AutoMapper::map($match->getParticipant2(), MatchParticipant::class, trackEntity: false) : null;
    
        if ($participant1 !== null){
            $this->entityManager->persist($participant1);
        }
        if ($participant2 !== null){
            $this->entityManager->persist($participant2);
        }

        if ($tournament->getMatchingType(false) === MatchingType::AllVsAll){

        }
        elseif ($tournament->getMatchingType(false) === MatchingType::Elimination){
            $this->checkSingleEliminationWinCondition($match, $tournament, $participant1?->getId(), $participant2?->getId());
        }

        if ($participant1 !== null || $participant2 !== null){
            $this->entityManager->flush();
        }
    }

    private function checkRoundRobinWinCondition()
    {

    }

    private function participant1Win(MatchModel $match, WinCondition $cond): bool
    {
        switch ($cond){
            case WinCondition::MaxPoints:
                return ($match->getParticipant1()?->getPoints() ?? 0) > ($match->getParticipant2()?->getPoints() ?? 0);
            case WinCondition::MinPoints:
                return ($match->getParticipant1()?->getPoints() ?? PHP_INT_MAX) < ($match->getParticipant2()?->getPoints() ?? PHP_INT_MAX);
            case WinCondition::MaxTime:
                return
                    ($match->getParticipant1()?->getCompletionTime() === null ? 0 : (int)$match->getParticipant1()?->getCompletionTime()?->format('%s'))
                    >
                    ($match->getParticipant2()?->getCompletionTime() === null ? 0 : (int)$match->getParticipant2()?->getCompletionTime()?->format('%s'));
            case WinCondition::MinTime:
                return
                    ($match->getParticipant1()?->getCompletionTime() === null ? PHP_INT_MAX : (int)$match->getParticipant1()?->getCompletionTime()?->format('%s'))
                    <
                    ($match->getParticipant2()?->getCompletionTime() === null ? PHP_INT_MAX : (int)$match->getParticipant2()?->getCompletionTime()?->format('%s'));
            default:
                throw new \InvalidArgumentException();
        }
    }

    private function checkSingleEliminationWinCondition(MatchModel $match, TournamentModel $tournament, ?int $matchParticipant1Id, ?int $matchParticipant2Id)
    {
        $firstWin = $this->participant1Win($match, $tournament->getWinCondition(false));

        if (($firstWin && $matchParticipant1Id === null) || (!$firstWin && $matchParticipant2Id === null)){
            return;
        }
        
        if ($match->getChildId() === null){
            $this->tournamentManager->setWinner($tournament, $firstWin ? $matchParticipant1Id : $matchParticipant2Id);
            return;
        }
        
        $nextMatch = $this->getMatch($match->getChildId());
        /** @var TournamentMatch */
        $nextMatchEntity = AutoMapper::map($nextMatch, TournamentMatch::class, trackEntity: false);
        /** @var MatchParticipant */
        $winnerOfMatch = AutoMapper::map($firstWin ? $match->getParticipant1() : $match->getParticipant2(), MatchParticipant::class, trackEntity: false);
        
        $matchPart1Id = $match->getParticipant1()?->getParticipantId();
        $matchPart2Id = $match->getParticipant2()?->getParticipantId();
        $nextMatchPart1Id = $nextMatch->getParticipant1()?->getParticipantId();
        $nextMatchPart2Id = $nextMatch->getParticipant2()?->getParticipantId();
        
        if ($nextMatchPart1Id !== null && ($nextMatchPart1Id === $matchPart1Id || $nextMatchPart1Id === $matchPart2Id)){
            /** @var MatchParticipant */
            $matchPart = AutoMapper::map($this->resetMatch($nextMatch->getParticipant1()), MatchParticipant::class, trackEntity: false);
            $matchPart->setTournamentParticipant($winnerOfMatch->getTournamentParticipant());
            $this->entityManager->persist($matchPart);
            return;
        }

        if ($nextMatchPart2Id !== null && ($nextMatchPart2Id === $matchPart1Id || $nextMatchPart2Id === $matchPart2Id)){
            /** @var MatchParticipant */
            $matchPart = AutoMapper::map($this->resetMatch($nextMatch->getParticipant2()), MatchParticipant::class, trackEntity: false);
            $matchPart->setTournamentParticipant($winnerOfMatch->getTournamentParticipant());
            $this->entityManager->persist($matchPart);
            return;
        }

        if ($nextMatchPart1Id !== null && $nextMatchPart2Id !== null){
            return;
        }

        /** create new entity for next match */
        $nextMatchPart = new MatchParticipant();
        $nextMatchPart
            ->setTournamentMatch($nextMatchEntity)
            ->setTournamentParticipant($winnerOfMatch->getTournamentParticipant());

        $this->entityManager->persist($nextMatchPart);
    }

    private function resetMatch(MatchParticipantModel $matchParticipant): MatchParticipantModel
    {
        return $matchParticipant
            ->setPoints(null)
            ->setCompletionTime(null);
    }
}
