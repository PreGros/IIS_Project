<?php

namespace App\DAL\Repository;

use App\DAL\Entity\MatchParticipant;
use App\DAL\Entity\Team;
use App\DAL\Entity\Tournament;
use App\DAL\Entity\TournamentParticipant;
use App\DAL\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentParticipant>
 *
 * @method TournamentParticipant|null find($id, $lockMode = null, $lockVersion = null)
 * @method TournamentParticipant|null findOneBy(array $criteria, array $orderBy = null)
 * @method TournamentParticipant[]    findAll()
 * @method TournamentParticipant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TournamentParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentParticipant::class);
    }

    public function save(TournamentParticipant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TournamentParticipant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findTeam(int $tournamentId, int $currUserId): TournamentParticipant
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
            ->select('p')
            ->from(TournamentParticipant::class, 'p')
            ->innerJoin(Team::class,'t',Join::WITH, 'IDENTITY(t.leader) = :p_currUserId AND t = p.signedUpTeam')
            ->where('IDENTITY(p.tournament) = :p_tournamentId')
            ->setParameter('p_tournamentId', $tournamentId)
            ->setParameter('p_currUserId', $currUserId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Paginator<array<<TournamentParticipant|int>>
     */
    public function findTableData(?int $currUserId, int $limit, int $start, string $order, bool $ascending, string $search, int $tournamentId): Paginator
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder
            ->select('p participant')
            ->addSelect('CASE WHEN (u.id IS NOT NULL) THEN u.isDeactivated ELSE tm.isDeactivated END as deactivatedP')
            ->addSelect('CASE WHEN (IDENTITY(t.createdBy) = :p_user_id ) THEN 1 ELSE 0 END as createdByCurrUser')
            ->from(TournamentParticipant::class, 'p')
            ->innerJoin(Tournament::class, 't', Join::WITH, 't.id = :p_tournament_id AND t = p.tournament')
            ->leftJoin(User::class, 'u', Join::WITH, 'p.signedUpUser = u')
            ->leftJoin(Team::class, 'tm', Join::WITH, 'p.signedUpTeam = tm')
            ->where('tm.name IS NOT NULL AND tm.name LIKE :p_search')
            ->orWhere('u.nickname IS NOT NULL AND u.nickname LIKE :p_search');

        if ($order !== ''){
            $queryBuilder
                ->orderBy(
                match($order){
                    'name' => 'tm.name',
                    'isApproved' => 'p.approved'
                },
                $ascending ?
                    'ASC' :
                    'DESC'
                );
            
            if ($order === 'name'){
                $queryBuilder
                    ->addOrderBy(
                        'u.nickname',
                        $ascending ?
                            'ASC' :
                            'DESC'
                    );
            }
        }
        
        $query = $queryBuilder
            ->setParameter('p_search', "%{$search}%")
            ->setParameter('p_tournament_id', $tournamentId)
            ->setParameter('p_user_id', $currUserId)
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query, false);
    }


    public function findOneByMatchParticipant(int $matchParticipantId): ?TournamentParticipant
    {
        return $this->createQueryBuilder('tp')
            ->innerJoin(MatchParticipant::class, 'mp', Join::WITH, 'tp = mp.tournamentParticipant')
            ->where('mp.id = :p_matchParticipantId')
            ->setParameter('p_matchParticipantId', $matchParticipantId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<TournamentParticipant|User|Team>
     */
    public function findNonAssignParticipants(int $tournamentId, int $includeParticipantId = 0, bool $oneMultipleTimes = false): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
            ->select('tp, u, t')
            ->from(TournamentParticipant::class, 'tp')
            ->leftJoin(User::class, 'u', Join::WITH, 'tp.signedUpUser = u')
            ->leftJoin(Team::class, 't', Join::WITH, 'tp.signedUpTeam = t')
            ->where($queryBuilder->expr()->orX(
                'p_oneMultipleTimes = 1',
                'NOT EXISTS(' . 
                    $this->createQueryBuilder('p')
                        ->innerJoin(MatchParticipant::class, 'mp', Join::WITH, 'mp.tournamentParticipant = p')
                        ->where('p.id = tp.id')
                        ->andWhere('p.id != :p_includeParticipantId')
                        ->getDQL()
                . ')')
            )
            ->andWhere('IDENTITY(tp.tournament) = :p_tournamentId')
            ->setParameter('p_includeParticipantId', $includeParticipantId)
            ->setParameter('p_tournamentId', $tournamentId)
            ->setParameter('p_oneMultipleTimes', (int)$oneMultipleTimes)
            ->getQuery()
            ->getResult();
    }



    public function findParticipantCount(int $tournamentId): ?int
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
            ->select('SUM(p.approved) as approvedCount')
            ->from(TournamentParticipant::class, 'p')
            ->where('IDENTITY(p.tournament) = :p_tournament_id')
            ->setParameter('p_tournament_id', $tournamentId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findParticipant(int $participantId): TournamentParticipant
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
            ->select('tp')
            ->from(TournamentParticipant::class, 'tp')
            ->leftJoin(User::class, 'u', Join::WITH, 'tp.signedUpUser = u')
            ->leftJoin(Team::class, 't', Join::WITH, 'tp.signedUpTeam = t')
            ->where('tp.id = :p_participant_id')
            ->setParameter('p_participant_id', $participantId)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return TournamentParticipant[] Returns an array of TournamentParticipant objects
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

//    public function findOneBySomeField($value): ?TournamentParticipant
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
