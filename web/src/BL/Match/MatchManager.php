<?php

namespace App\BL\Tournament;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use App\BL\Util\AutoMapper;
use App\BL\Util\StringUtil;
use App\BL\Util\DataTableState;

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

    private function generateMatchesRoundRobin()
    {   
        // number of participants
        $count = 2000;
        $matches = [];

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
                    $matches[] = [$first, $second];
                }
            }
            /** rotate right linked list without first element  */
            $linkedList->add(1, $linkedList->pop());
        }

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

    private function generateMatchesSingleElimination()
    {
        // number of participants
        $countOfParticipants = 8;
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
                    $matches[$index++] = [$linkedList->shift(), $linkedList->shift()];
                    $linkedList->push($c++);
                }

                if ($oddMatches){
                    $linkedList->push($linkedList->shift());
                }
            }
            else{
                for ($i = $matchesPerLevel - 1; $i >= 0; $i--){
                    $matches[$index + $i] = [$linkedList->pop(), $linkedList->pop()];
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
    }
}
