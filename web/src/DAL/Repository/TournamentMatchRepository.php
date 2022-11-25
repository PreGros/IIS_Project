<?php

namespace App\DAL\Repository;

use App\BL\Tournament\WinCondition;
use App\DAL\Entity\MatchParticipant;
use App\DAL\Entity\Team;
use App\DAL\Entity\Tournament;
use App\DAL\Entity\TournamentMatch;
use App\DAL\Entity\TournamentParticipant;
use App\DAL\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentMatch>
 *
 * @method TournamentMatch|null find($id, $lockMode = null, $lockVersion = null)
 * @method TournamentMatch|null findOneBy(array $criteria, array $orderBy = null)
 * @method TournamentMatch[]    findAll()
 * @method TournamentMatch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TournamentMatchRepository extends ServiceEntityRepository
{
    private const TIME_FORMAT = '+P%yY%mM%dDT%kH%iM%sS';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentMatch::class);
    }

    public function save(TournamentMatch $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TournamentMatch $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllWithParticipants(int $tournamentId, ?int $participantId = null): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('m, p, tp, u, tm')
            ->from(TournamentMatch::class, 'm')
            ->leftJoin(MatchParticipant::class, 'p', Join::WITH, 'p.tournamentMatch = m')
            ->leftJoin(TournamentParticipant::class, 'tp', Join::WITH, 'p.tournamentParticipant = tp')
            ->leftJoin(User::class, 'u', Join::WITH, 'tp.signedUpUser = u')
            ->leftJoin(Team::class, 'tm', Join::WITH, 'tp.signedUpTeam = tm')
            ->where('IDENTITY(m.tournament) = :p_tournament_id');
        
        if ($participantId !== null){
            $queryBuilder = $queryBuilder->andWhere('m.id IN (' .
                $this->getEntityManager()->createQueryBuilder()
                ->select('tour_match.id')
                ->from(TournamentMatch::class, 'tour_match')
                ->leftJoin(MatchParticipant::class, 'match_p', Join::WITH, 'match_p.tournamentMatch = tour_match')
                ->leftJoin(TournamentParticipant::class, 'tour_p', Join::WITH, 'match_p.tournamentParticipant = tour_p')
                ->where('(CASE WHEN tour_p.signedUpUser IS NOT NULL THEN IDENTITY(tour_p.signedUpUser) ELSE IDENTITY(tour_p.signedUpTeam) END) = :p_participant_id')
                ->getDQL() .
                ')'
            );
        }

        $queryBuilder = $queryBuilder    
            ->orderBy('m.id')
            ->addOrderBy('p.id')
            ->setParameter('p_tournament_id', $tournamentId);

        if ($participantId !== null){
            $queryBuilder = $queryBuilder->setParameter('p_participant_id', $participantId);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function findWithParticipants(int $matchId): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('m, p, tp, u, tm, pm')
            ->from(TournamentMatch::class, 'm')
            ->leftJoin(MatchParticipant::class, 'p', Join::WITH, 'p.tournamentMatch = m')
            ->leftJoin(TournamentParticipant::class, 'tp', Join::WITH, 'p.tournamentParticipant = tp')
            ->leftJoin(User::class, 'u', Join::WITH, 'tp.signedUpUser = u')
            ->leftJoin(Team::class, 'tm', Join::WITH, 'tp.signedUpTeam = tm')
            ->leftJoin(TournamentMatch::class, 'pm', Join::WITH, 'pm.childMatch = m')
            ->where('m.id = :p_match_id')
            ->orderBy('m.id')
            ->addOrderBy('p.id')
            ->setParameter('p_match_id', $matchId)
            ->getQuery()
            ->getResult();
    }

    public function findMatchesWithoutResult(int $tournamentId, int $matchParticipantCount): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
            ->select('m.id')
            ->from(TournamentMatch::class, 'm')
            ->leftJoin(MatchParticipant::class, 'p', Join::WITH, $queryBuilder->expr()->andX(
                    'p.tournamentMatch = m',
                    $queryBuilder->expr()->orX(
                        'p.points IS NOT NULL',
                        'p.completionTime IS NOT NULL'
                    )
                )
            )
            ->where('IDENTITY(m.tournament) = :p_tournamentId')
            ->groupBy('m')
            ->having('COUNT(p) < :p_matchParticipantCount')
            ->setParameter('p_tournamentId', $tournamentId)
            ->setParameter('p_matchParticipantCount', $matchParticipantCount)
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function findTournamentWinner(int $tournamentId, ?bool $pointsGrater, ?bool $timeGrater)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
            ->select('tp')
            ->from(TournamentParticipant::class, 'tp')
            ->innerJoin(MatchParticipant::class, 'p', Join::WITH, 'p.tournamentParticipant = tp')
            ->where('IDENTITY(tp.tournament) = :p_tournamentId')
            ->groupBy('tp')
            ->having($queryBuilder->expr()->andX(
                ':p_pointsGrater = 1',
                'SUM(p.points) >= ALL (' .  $this->getSumPointsQuery('pp1', $tournamentId) .')'
            ))
            ->orHaving($queryBuilder->expr()->andX(
                ':p_pointsGrater = 0',
                'SUM(p.points) <= ALL (' .  $this->getSumPointsQuery('pp2', $tournamentId) .')'
            ))
            ->orHaving($queryBuilder->expr()->andX(
                ':p_timeGrater = 1',
                'SUM(TIMETOSEC(STRTODATE(p.completionTime, \'' . self::TIME_FORMAT . '\'))) >= ALL (' .  $this->getSumDurationQuery('pd1', $tournamentId) .')'
            ))
            ->orHaving($queryBuilder->expr()->andX(
                ':p_timeGrater = 0',
                'SUM(TIMETOSEC(STRTODATE(p.completionTime, \'' . self::TIME_FORMAT . '\'))) <= ALL (' .  $this->getSumDurationQuery('pd2', $tournamentId) .')'
            ))
            ->setParameter('p_tournamentId', $tournamentId)
            ->setParameter('p_pointsGrater', $pointsGrater)
            ->setParameter('p_timeGrater', $timeGrater)
            ->getQuery()
            ->getResult();
    }

    private function getSumPointsQuery(string $alias, int $tournamentId): string
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
            ->select('SUM(' . $alias . '.points)')
            ->from(TournamentParticipant::class, 't' . $alias)
            ->innerJoin(MatchParticipant::class, $alias, Join::WITH, $alias . '.tournamentParticipant = t' . $alias)
            ->where('IDENTITY(t' . $alias . '.tournament) = :p_tournamentId')
            ->groupBy('t' . $alias . '.id')
            ->setParameter('p_tournamentId', $tournamentId)
            ->getDQL();
    }

    private function getSumDurationQuery(string $alias, int $tournamentId): string
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
            ->select('SUM(TIMETOSEC(STRTODATE(' . $alias . '.completionTime, \'' . self::TIME_FORMAT . '\')))')
            ->from(TournamentParticipant::class, 't' . $alias)
            ->innerJoin(MatchParticipant::class, $alias, Join::WITH, $alias . '.tournamentParticipant = t' . $alias)
            ->where('IDENTITY(t' . $alias . '.tournament) = :p_tournamentId')
            ->groupBy('t' . $alias . '.id')
            ->setParameter('p_tournamentId', $tournamentId)
            ->getDQL();
    }

//    /**
//     * @return TournamentMatch[] Returns an array of TournamentMatch objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TournamentMatch
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
