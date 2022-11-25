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
use App\BL\Tournament\TournamentParticipantModel;
use App\BL\Tournament\WinCondition;
use App\BL\User\UserModel;
use App\BL\Util\DateTimeUtil;
use App\DAL\Entity\MatchParticipant;
use App\DAL\Entity\TournamentMatch;
use App\DAL\Entity\TournamentParticipant;

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
            if ($node->getChild()?->getId() !== null){
                $matches[$node->getChild()?->getId()]->addPreviousMatch($node);
            }
            if (in_array($node->getId(), isset($layers[$layer]) ? array_map(fn (MatchModel $m) => $m->getChild()?->getId(), $layers[$layer]) : [])){
                if ($layer !== 0){
                    $children = array_map(fn (MatchModel $m) => $m->getChild()?->getId(), $layers[$layer - 1]);
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
        $matchIndex = 0;
        $matchParticipantCount = 0;
        for ($i = 0; $i < count($entities); $i++){
            if ($entities[$i] instanceof \App\DAL\Entity\TournamentMatch){
                /** @var MatchModel $match */
                $match = AutoMapper::map($entities[$i], MatchModel::class, trackEntity: false);
                $match->setChild($entities[$i]->getChildMatch() !== null ? AutoMapper::map($entities[$i]->getChildMatch(), MatchModel::class, trackEntity: false) : null);
                $matchIndex = $match->getId();
                $matches[$matchIndex] = $match;
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
        return $matchP
            ->setTournamentPartId($tournamentP->getId())
            ->setParticipant($team ?? $user ?? null);
    }

    public function generateMatches(TournamentModel $tournament, \DateInterval $matchDuration, \DateInterval $breakDuration, bool $setParticipantsToMatches = true)
    {
        /** @var \App\DAL\Repository\TournamentParticipantRepository */
        $participantsRepo = $this->entityManager->getRepository(\App\DAL\Entity\TournamentParticipant::class);
        $participants = $participantsRepo->findBy(['tournament' => AutoMapper::map($tournament, Tournament::class, trackEntity: false), 'approved' => true]);

        $this->matchGenerator->init($participants, $tournament, $matchDuration, $breakDuration, $setParticipantsToMatches);
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
        $match->setChild($entities[0]->getChildMatch() !== null ? AutoMapper::map($entities[0]->getChildMatch(), MatchModel::class, trackEntity: false) : null);
        
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
                continue;
            }

            if ($entity instanceof \App\DAL\Entity\TournamentMatch && $entity->getId() !== $match->getId()){
                $match->addPreviousMatch(AutoMapper::map($entity, MatchModel::class));
            }
        }
        return $match;
    }

    public function checkParticpants(MatchModel $match, ?int $firstParticipant, ?int $secondParticipant): ?string
    {
        $bothDisabled = $match->getPreviousMatchesCount() === 2;
        $disableEnable = $match->getPreviousMatchesCount() > 0;
        $firstDisabled = $disableEnable && $match->getParticipant1() === null;

        if ($firstParticipant !== $match->getParticipant1()?->getTournamentPartId() && $disableEnable && ($bothDisabled || $firstDisabled)){
            return 'Cannot change first participant - first participant is a winner of previous match';
        }

        if ($secondParticipant !== $match->getParticipant2()?->getTournamentPartId() && $disableEnable && ($bothDisabled || !$firstDisabled)){
            return 'Cannot change second participant - second participant is a winner of previous match';
        }

        if ($firstParticipant !== null && $firstParticipant === $secondParticipant){
            return 'Participants cannot be same';
        }
        return null;
    }

    public function editMatch(MatchModel $match, ?int $firstParticipant, ?int $secondParticipant)
    {
        /** @var TournamentMatch */
        $matchEntity = AutoMapper::map($match, TournamentMatch::class, trackEntity: false);
        $this->entityManager->persist($matchEntity);

        $this->updateOrCreateMatchParticipant(
            $match->getParticipant1(),
            $matchEntity,
            $firstParticipant !== null ? $this->entityManager->getReference(\App\DAL\Entity\TournamentParticipant::class, $firstParticipant) : null
        );

        $this->updateOrCreateMatchParticipant(
            $match->getParticipant2(),
            $matchEntity,
            $secondParticipant !== null ? $this->entityManager->getReference(\App\DAL\Entity\TournamentParticipant::class, $secondParticipant) : null
        );

        $this->entityManager->flush();
    }

    private function updateOrCreateMatchParticipant(?MatchParticipantModel $matchPart, TournamentMatch $matchEntity, ?TournamentParticipant $tournamentPart)
    {
        if ($tournamentPart === null){
            if ($matchPart !== null){
                $this->entityManager->remove(AutoMapper::map($matchPart, MatchParticipant::class, trackEntity: false));
            }
            return;
        }

        if ($matchPart !== null){
            /** @var MatchParticipant */
            $matchPart = AutoMapper::map($this->resetMatch($matchPart), MatchParticipant::class, trackEntity: false);
            $matchPart->setTournamentParticipant($tournamentPart);
        }
        else{
            $matchPart = new MatchParticipant();
            $matchPart
                ->setTournamentMatch($matchEntity)
                ->setTournamentParticipant($tournamentPart);
        }
        
        $this->entityManager->persist($matchPart);
    }

    public function setMatchResult(MatchModel $match, TournamentModel $tournament)
    {
        $participant1 = $match->getParticipant1() !== null ? AutoMapper::map($match->getParticipant1(), MatchParticipant::class, trackEntity: false) : null;
        $participant2 = $match->getParticipant2() !== null ? AutoMapper::map($match->getParticipant2(), MatchParticipant::class, trackEntity: false) : null;
    
        if ($participant1 === null && $participant2 === null){
            return;
        }

        if ($participant1 !== null){
            $this->entityManager->persist($participant1);
        }
        if ($participant2 !== null){
            $this->entityManager->persist($participant2);
        }

        if ($tournament->getMatchingType(false) === MatchingType::AllVsAll){
            /** For round robin all results have to be set in db */
            $this->entityManager->flush();
            $this->checkRoundRobinWinCondition($tournament);
        }
        elseif ($tournament->getMatchingType(false) === MatchingType::Elimination){
            $this->checkSingleEliminationWinConditionAndSetResult($match, $tournament);
        }

        $this->entityManager->flush();
    }

    private function checkRoundRobinWinCondition(TournamentModel $tournament)
    {
        /** @var \App\DAL\Repository\TournamentMatchRepository */
        $repo = $this->entityManager->getRepository(TournamentMatch::class);

        $matchesWithoutResult = $repo->findMatchesWithoutResult($tournament->getId(), 2);
        if (!empty($matchesWithoutResult)){
            /** we need all result to determinate winner */
            return;
        }
        $winCond = $tournament->getWinCondition(false);
        $pointsGrater = $winCond === WinCondition::MaxPoints || $winCond === WinCondition::MinPoints ? $winCond === WinCondition::MaxPoints : null; 
        $durationGrater = $winCond === WinCondition::MaxTime || $winCond === WinCondition::MinTime ? $winCond === WinCondition::MaxTime : null;
        $tournamentWinners = $repo->findTournamentWinner($tournament->getId(), $pointsGrater, $durationGrater);
        if (empty($matchesWithoutResult)){
            /** Invalid tournaments data */
            return;
        }
        /** if multiple participants have same result, first one is taken */
        $this->tournamentManager->setWinner($tournament, $tournamentWinners[array_key_first($tournamentWinners)]->getId(), false);
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
                    ($match->getParticipant1()?->getCompletionTime() === null ? 0 : DateTimeUtil::dateIntervalToSeconds($match->getParticipant1()->getCompletionTime()))
                    >
                    ($match->getParticipant2()?->getCompletionTime() === null ? 0 : DateTimeUtil::dateIntervalToSeconds($match->getParticipant2()->getCompletionTime()));
            case WinCondition::MinTime:
                return
                    ($match->getParticipant1()?->getCompletionTime() === null ? PHP_INT_MAX : DateTimeUtil::dateIntervalToSeconds($match->getParticipant1()->getCompletionTime()))
                    <
                    ($match->getParticipant2()?->getCompletionTime() === null ? PHP_INT_MAX : DateTimeUtil::dateIntervalToSeconds($match->getParticipant2()->getCompletionTime()));
            default:
                throw new \InvalidArgumentException();
        }
    }

    private function checkSingleEliminationWinConditionAndSetResult(MatchModel $match, TournamentModel $tournament)
    {
        $firstWin = $this->participant1Win($match, $tournament->getWinCondition(false));

        $tournamentPart1Id = $match->getParticipant1()?->getTournamentPartId();
        $tournamentPart2Id = $match->getParticipant2()?->getTournamentPartId();

        if (($firstWin && $tournamentPart1Id === null) || (!$firstWin && $tournamentPart2Id === null)){
            return;
        }
        
        if ($match->getChild()?->getId() === null){
            $this->tournamentManager->setWinner($tournament, $firstWin ? $tournamentPart1Id : $tournamentPart2Id);
            return;
        }
        
        $nextMatch = $this->getMatch($match->getChild()?->getId());
        /** @var TournamentMatch */
        $nextMatchEntity = AutoMapper::map($nextMatch, TournamentMatch::class, trackEntity: false);
        /** @var MatchParticipant */
        $winnerOfMatch = AutoMapper::map($firstWin ? $match->getParticipant1() : $match->getParticipant2(), MatchParticipant::class, trackEntity: false);
        if (!$nextMatch->hasPreviousMatch($match->getId())){
            return;
        }
        
        $nextMatchPart = $nextMatch->isWinnerFirstParticipant($match) ? $nextMatch->getParticipant1() : $nextMatch->getParticipant2();
        $matchPart = null;

        if ($nextMatchPart !== null){
            $matchPart = AutoMapper::map($this->resetMatch($nextMatchPart), MatchParticipant::class, trackEntity: false);
            $matchPart->setTournamentParticipant($winnerOfMatch->getTournamentParticipant());
        }
        else{
            $matchPart = new MatchParticipant();
            $matchPart
                ->setTournamentMatch($nextMatchEntity)
                ->setTournamentParticipant($winnerOfMatch->getTournamentParticipant());
        }
        
        $this->entityManager->persist($matchPart);
    }

    private function resetMatch(MatchParticipantModel $matchParticipant): MatchParticipantModel
    {
        return $matchParticipant
            ->setPoints(null)
            ->setCompletionTime(null);
    }

    /**
     * @return \Traversable<TournamentParticipantModel>
     */
    public function getTournamentParticipants(int $tournamentId, int $includeParticipantId = 0)
    {   
        /** @var \App\DAL\Repository\TournamentParticipantRepository */
        $repo = $this->entityManager->getRepository(\App\DAL\Entity\TournamentParticipant::class);

        $entities = $repo->findNonAssignParticipants($tournamentId, $includeParticipantId);

        for ($i = 0; $i < count($entities); $i++){
            /** @var TournamentParticipantModel $model */
            $model = AutoMapper::map($entities[$i++], TournamentParticipantModel::class);
            $userEntity = $entities[$i++];
            $user = $userEntity === null ? null : AutoMapper::map($userEntity, UserModel::class, trackEntity: false);
            $teamEntity = $entities[$i];
            $team = $teamEntity === null ? null : AutoMapper::map($teamEntity, TeamModel::class, trackEntity: false);
            $model->setParticipant($user ?? $team);
            yield $model;
        }
    }

    /**
     * @return array<TournamentParticipantModel>
     */
    public function getFormatedTournamentParticipants(int $tournamentId, ?int $includeParticipantId = null): array
    {
        $participants = [];
        foreach ($this->getTournamentParticipants($tournamentId, $includeParticipantId ?? 0) as $participant){
            if (($name = $participant->getParticipantName()) !== ''){
                $participants[$name] = $participant->getId();
            }
        }
        return $participants;
    }
}
