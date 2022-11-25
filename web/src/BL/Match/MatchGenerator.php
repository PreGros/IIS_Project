<?php

namespace App\BL\Match;

use App\BL\Tournament\TournamentModel;
use Doctrine\ORM\EntityManagerInterface;

use App\BL\Util\AutoMapper;
use App\DAL\Entity\MatchParticipant;
use App\DAL\Entity\Tournament;
use App\DAL\Entity\TournamentMatch;
use App\DAL\Entity\TournamentParticipant;

class MatchGenerator
{
    private EntityManagerInterface $entityManager;

    /** @var array<TournamentParticipant> */
    private array $participants;

    private int $countOfParticipants;

    private Tournament $tournament;

    private bool $setParticipantsToMatches = true;

    private \DateTime $startDate;

    private \DateInterval $matchDuration;

    private \DateInterval $breakDuration;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @var array<TournamentParticipant> $participants
     */
    public function init(
        array $participants,
        TournamentModel $tournamentModel,
        \DateInterval $matchDuration,
        \DateInterval $breakDuration,
        bool $setParticipantsToMatches = true
    )
    {
        /** randomize participants order */
        shuffle($participants);
        $this->participants = $participants;
        $this->tournament = AutoMapper::map($tournamentModel, Tournament::class, trackEntity: false);
        $this->setParticipantsToMatches = $setParticipantsToMatches;
        $this->countOfParticipants = count($participants);
        $this->matchDuration = $matchDuration;
        $this->breakDuration = $breakDuration;
        /** first tournament will start at time specified in tournament */
        $this->startDate = \DateTime::createFromInterface($tournamentModel->getDate())->sub($matchDuration)->sub($breakDuration);
    }

    private function getNextStartDate(): \DateTime
    {
       $this->startDate->add($this->matchDuration)->add($this->breakDuration);
       return clone $this->startDate;
    }

    public function generateMatchesRoundRobin()
    {
        /** odd number needs to have imaginary match which acts like a break */
        $odd = $this->countOfParticipants % 2 !== 0;
        $n = $odd ? $this->countOfParticipants + 1 : $this->countOfParticipants;

        $linkedList = new \SplDoublyLinkedList();
        for ($i = 0; $i < $n; $i++){
            $linkedList->push($i);
        }

        /** 
         * algorithm takes first with last element, second with second to last and so on
         * algorithm does n - 1 iterations (n-th iteration would have been same as first)
         */
        for ($i = 0; $i < $n - 1; $i++){
            for ($j = 0; $j < $n / 2; $j++){
                $first = $linkedList->offsetGet($j);
                $second = $linkedList->offsetGet($n - $j - 1);
                /** check if match does not contain imaginary match */
                if (!$odd || ($first !== $n - 1 && $second !== $n - 1)){
                    $this->entityManager->persist($this->createMatchRoundRobin($first, $second));
                }
            }
            /** rotate right linked list without first element  */
            $linkedList->add(1, $linkedList->pop());
        }

        $this->entityManager->flush();

        /**
         * optimized version (faster and needs less memory), but much less readable
         * 
         *   for ($i = 0; $i < $n - 1; $i++){
         *       for ($j = 0; $j < ($n / 2); $j++){
         *           // $j - $i -> rotate right the index + $n -> convert index to positive, all -1 for shift to zero
         *           $f = $j === 0 ? 0 : ((($j - 1 - $i) + $n - 1) % ($n - 1)) + 1; // all indexes apart form the 0 have to be rotated -> shift to zero, compute, shift back
         *           $sn = $n - $j - 1;
         *           $s = $sn === 0 ? 0 : ((($sn - 1 - $i) + $n - 1) % ($n - 1)) + 1; // all indexes apart form the 0 have to be rotated -> shift to zero, compute, shift back
         *           if (!$odd || ($f !== $n - 1 && $s !== $n - 1)){ // check if match does not contain imaginary match
         *               $this->entityManager->persist($this->createMatchRoundRobin($first, $second));
         *           }
         *       }
         *   }
         */
    }

    public function generateMatchesSingleElimination()
    {
        $countOfParticipants = count($this->participants);
        /** @var array<\App\DAL\Entity\TournamentMatch> */
        $matches = [];

        $linkedList = new \SplDoublyLinkedList();
        for ($i = 0; $i < $countOfParticipants; $i++){
            $linkedList->push($i);
        }

        $fromLeft = false;
        $c = $countOfParticipants;
        $index = 0;
        /** generete next levels while participants are available */
        while (($count = $linkedList->count()) > 1){
            /** switch sides so one match cannot go straight to the finals */
            $fromLeft = !$fromLeft;

            $matchesPerLevel = floor($count / 2);
            $oddMatches = $count % 2;

            if ($fromLeft){
                for ($i = 0; $i < $matchesPerLevel; $i++){
                    $match = $this->createMatchSingleElimination($linkedList->shift(), $linkedList->shift(), $matches);
                    $matches[$index++] = $match;
                    $this->entityManager->persist($match);
                    $linkedList->push($c++);
                }

                if ($oddMatches){
                    $linkedList->push($linkedList->shift());
                }

                continue;
            }
            
            /** form right */
            for ($i = $matchesPerLevel - 1; $i >= 0; $i--){
                $match = $this->createMatchSingleElimination($linkedList->pop(), $linkedList->pop(), $matches);
                $matches[$index + $i] = $match;
                $this->entityManager->persist($match);
                $linkedList->unshift($c++);
            }

            $index += $matchesPerLevel;
            if ($oddMatches){
                $linkedList->unshift($linkedList->pop());
            }
        }

        $this->entityManager->flush();
    }

    private function createMatchRoundRobin(int $first, int $second): TournamentMatch
    {
        $match = new TournamentMatch();
        $match
            ->setDuration($this->matchDuration)
            ->setStartTime($this->getNextStartDate())
            ->setTournament($this->tournament);

        if (!$this->setParticipantsToMatches){
            return $match;
        }

        if ($first < $this->countOfParticipants){
            $this->entityManager->persist($this->createMatchParticipant($match, $this->participants[$first]));
        }
        if ($second < $this->countOfParticipants){
            $this->entityManager->persist($this->createMatchParticipant($match, $this->participants[$second]));
        }

        return $match;
    }

    private function createMatchSingleElimination(int $first, int $second, array $matches): TournamentMatch
    {
        $match = new TournamentMatch();
        if ($first >= $this->countOfParticipants){
            $match->addTournamentMatch($matches[$first - $this->countOfParticipants]);
        }
        if ($second >= $this->countOfParticipants){
            $match->addTournamentMatch($matches[$second - $this->countOfParticipants]);
        }
        $match
            ->setDuration($this->matchDuration)
            ->setStartTime($this->getNextStartDate())
            ->setTournament($this->tournament);

        if (!$this->setParticipantsToMatches){
            return $match;   
        }

        if ($first < $this->countOfParticipants){
            $this->entityManager->persist($this->createMatchParticipant($match, $this->participants[$first]));
        }
        if ($second < $this->countOfParticipants){
            $this->entityManager->persist($this->createMatchParticipant($match, $this->participants[$second]));
        }

        return $match;
    }

    private function createMatchParticipant(TournamentMatch $tournamentMatch, TournamentParticipant $tournamentParticipant): MatchParticipant
    {
        return (new MatchParticipant())
            ->setTournamentMatch($tournamentMatch)
            ->setTournamentParticipant($tournamentParticipant);
    }
}
