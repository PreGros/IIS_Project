<?php

namespace App\DAL\Repository;

use App\DAL\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Team>
 *
 * @method Team|null find($id, $lockMode = null, $lockVersion = null)
 * @method Team|null findOneBy(array $criteria, array $orderBy = null)
 * @method Team[]    findAll()
 * @method Team[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    public function save(Team $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Team $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<\App\DAL\Entity\User>
     */
    public function findNewMembers(int $teamId, string $query, int $limit): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('u')
            ->from(\App\DAL\Entity\User::class, 'u')
            ->andWhere('u.id != ' . '(' . $this->getEntityManager()->createQueryBuilder()
                ->select('IDENTITY(t.leader)')
                ->from(\App\DAL\Entity\Team::class, 't')
                ->where('t.id = :teamId')
                ->getDQL() . ')')
            ->andWhere('NOT EXISTS '. '(' . $this->getEntityManager()->createQueryBuilder()
                ->select('m')
                ->from(\App\DAL\Entity\Member::class, 'm')
                ->where('IDENTITY(m.team) = :teamId')
                ->andWhere('IDENTITY(m.user) = u.id')
                ->getDQL() . ')')
            ->andWhere('u.nickname like :query')
            ->andWhere('u.isDeactivated = 0')
            ->setParameter('teamId', $teamId)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<array<bool|\App\DAL\Entity\User>>
     */
    public function findTeamMembers(int $teamId, int $limit)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
            ->select('u as user')
            ->addSelect('CASE WHEN t.id IS NOT NULL THEN 1 ELSE 0 END as isLeader')
            ->from(\App\DAL\Entity\User::class, 'u')
            ->leftJoin(\App\DAL\Entity\Team::class, 't', Expr\Join::WITH, $queryBuilder->expr()->andX(
                't.id = :teamId',
                'IDENTITY(t.leader) = u.id'
            ))
            ->leftJoin(\App\DAL\Entity\Member::class, 'm', Expr\Join::WITH, $queryBuilder->expr()->andX(
                'IDENTITY(m.team) = :teamId',
                'IDENTITY(m.user) = u.id'
            ))
            ->where('t.id IS NOT NULL')
            ->orWhere('IDENTITY(m) IS NOT NULL')
            ->setParameter('teamId', $teamId)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return array<array<bool|\App\DAL\Entity\Team>>
    //  */
    // public function findTeamParticipant(int $tournamentId, int $userId)
    // {
    //     $queryBuilder = $this->getEntityManager()->createQueryBuilder();

    //     return $queryBuilder
    //         ->select('t as team')
    //         ->addSelect('CASE WHEN t.id IS NOT NULL THEN 1 ELSE 0 END as isLeader')
    //         ->from(\App\DAL\Entity\Tournament::class, 'tr')
    //         ->innerJoin(\App\DAL\Entity\Team::class, 't', Expr\Join::WITH, $queryBuilder->expr()->andX(
    //             'tr.id = :p_tournamentId',
    //             'IDENTITY(t.leader) = u.id'
    //         ))
    //         ->leftJoin(\App\DAL\Entity\Member::class, 'm', Expr\Join::WITH, $queryBuilder->expr()->andX(
    //             'IDENTITY(m.team) = :teamId',
    //             'IDENTITY(m.user) = u.id'
    //         ))
    //         ->where('tr.id = :p_tournamentId')
    //         ->orWhere('IDENTITY(m) IS NOT NULL')
    //         ->setParameter('p_tournamentId', $tournamentId)
    //         ->getQuery()
    //         ->getResult();
    // }

    /**
     * @return Paginator<array<Team|int>>
     */
    public function findTableData(int $limit, int $start, string $order, bool $ascending, string $search, int $searchMemberCount): Paginator
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder
            ->select('count(m.user) memberCount, t team')
            ->from(Team::class, 't')
            ->innerJoin(\App\DAL\Entity\User::class, 'l', Expr\Join::WITH, 't.leader = l')
            ->leftJoin(\App\DAL\Entity\Member::class, 'm', Expr\Join::WITH, 'm.team = t')
            ->groupBy('t')
            ->addGroupBy('l')
            ->having($queryBuilder->expr()->orX(
                't.name LIKE :p_search',
                'l.nickname LIKE :p_search',
                'memberCount = :p_search_count'
            ))
            ->andHaving('t.isDeactivated = 0');

        if ($order !== ''){
            $queryBuilder
                ->orderBy(
                match($order){
                    'name' => 't.name',
                    'leaderNickName' => 'l.nickname',
                    'memberCount' => 'memberCount'
                },
                $ascending ?
                    'ASC' :
                    'DESC'
                );
        }
        
        $query = $queryBuilder
            ->setParameter('p_search', "%{$search}%")
            ->setParameter('p_search_count', $searchMemberCount)
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query, false);
    }

    public function findTeamWithCount(int $idTeam)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
            ->select('count(m.user) memberCount, t team')
            ->from(Team::class, 't')
            ->leftJoin(\App\DAL\Entity\Member::class, 'm', Expr\Join::WITH, 'm.team = t')
            ->groupBy('t')
            ->Having('t.isDeactivated = 0')
            ->andHaving('t.id = :p_team_id')
            ->setParameter('p_team_id', $idTeam)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Team[] Returns an array of Team objects
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

//    public function findOneBySomeField($value): ?Team
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
