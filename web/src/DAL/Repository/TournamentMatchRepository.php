<?php

namespace App\DAL\Repository;

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

    public function findAllWithParticipants(int $tournamentId): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('m, p, tp, u, tm')
            ->from(TournamentMatch::class, 'm')
            ->leftJoin(MatchParticipant::class, 'p', Join::WITH, 'p.tournamentMatch = m')
            ->leftJoin(TournamentParticipant::class, 'tp', Join::WITH, 'p.tournamentParticipant = tp')
            ->leftJoin(User::class, 'u', Join::WITH, 'tp.signedUpUser = u')
            ->leftJoin(Team::class, 'tm', Join::WITH, 'tp.signedUpTeam = tm')
            ->where('IDENTITY(m.tournament) = :p_tournament_id')
            ->orderBy('m.id')
            ->addOrderBy('p.id')
            ->setParameter('p_tournament_id', $tournamentId)
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
            ->setParameter('p_match_id', $matchId)
            ->getQuery()
            ->getResult();
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
