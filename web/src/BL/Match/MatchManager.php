<?php

namespace App\BL\Match;

use App\BL\Tournament\MatchingType;
use App\BL\Tournament\TournamentModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use App\BL\Util\AutoMapper;
use App\BL\Util\StringUtil;
use App\BL\Util\DataTableState;
use App\DAL\Entity\TournamentMatch;

class MatchManager
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security
    )
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function generateMatchesRoundRobin()
    {   
        $tournament = $this->entityManager->getReference(\App\DAL\Entity\Tournament::class, 1);
        // number of participants
        $count = 10;

        /** odd number need to have imaginary match which acts like a break */
        $odd = $count % 2 !== 0;
        $n = $odd ? $count + 1 : $count;

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
                    $match = new TournamentMatch();
                    $match
                        ->setDuration(new \DateInterval("PT{$first}M{$second}S"))
                        ->setStartTime(new \DateTime())
                        ->setTournament($tournament);
                    $this->entityManager->persist($match);
                    //$matches[] = [$first, $second];
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
         *               $matches[] = [$f, $s];
         *           }
         *       }
         *   }
         */
    }

    public function generateMatchesSingleElimination()
    {
        $tournament = $this->entityManager->getReference(\App\DAL\Entity\Tournament::class, 2);
        // number of participants
        $countOfParticipants = 16;
        /** @var array<\App\DAL\Entity\TournamentMatch> */
        $matches = [];

        $linkedList = new \SplDoublyLinkedList();
        for ($i = 0; $i < $countOfParticipants; $i++){
            $linkedList->push($i);
        }

        $fromLeft = true;
        $c = $countOfParticipants;
        $index = 0;
        /** generete next levels while participants are available */
        while (($count = $linkedList->count()) > 1){
            $matchesPerLevel = floor($count / 2);
            $oddMatches = $count % 2;

            if ($fromLeft){
                for ($i = 0; $i < $matchesPerLevel; $i++){
                    $first = $linkedList->shift();
                    $second = $linkedList->shift();
                    $match = new TournamentMatch();
                    if ($first >= $countOfParticipants){
                        $match->addTournamentMatch($matches[$first - $countOfParticipants]);
                    }
                    if ($second >= $countOfParticipants){
                        $match->addTournamentMatch($matches[$second - $countOfParticipants]);
                    }
                    $match
                        ->setDuration(new \DateInterval("PT{$first}M{$second}S"))
                        ->setStartTime(new \DateTime())
                        ->setTournament($tournament);
                    $matches[$index++] = $match;
                    $this->entityManager->persist($match);
                    $linkedList->push($c++);
                }

                if ($oddMatches){
                    $linkedList->push($linkedList->shift());
                }
            }
            else{
                for ($i = $matchesPerLevel - 1; $i >= 0; $i--){
                    $first = $linkedList->pop();
                    $second = $linkedList->pop();
                    $match = new TournamentMatch();
                    if ($first >= $countOfParticipants){
                        $match->addTournamentMatch($matches[$first - $countOfParticipants]);
                    }
                    if ($second >= $countOfParticipants){
                        $match->addTournamentMatch($matches[$second - $countOfParticipants]);
                    }
                    $match
                        ->setDuration(new \DateInterval("PT{$first}M{$second}S"))
                        ->setStartTime(new \DateTime())
                        ->setTournament($tournament);
                    $matches[$index + $i] = $match;
                    $this->entityManager->persist($match);
                    $linkedList->unshift($c++);
                }

                $index += $matchesPerLevel;
                if ($oddMatches){
                    $linkedList->unshift($linkedList->pop());
                }
            }
            /** switch sides so one match cannot go straight to the finals */
            $fromLeft = !$fromLeft;
        }

        $this->entityManager->flush();
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
}
