<?php

namespace App\DAL\Repository;

use App\DAL\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;

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

    /**
     * @return array<array<int|Team>>
     */
    public function findTableData(int $limit): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('count(m.user) memberCount, t team')
            ->from(Team::class, 't')
            ->innerJoin(\App\DAL\Entity\User::class, 'l', Expr\Join::WITH, 't.leader = l')
            ->leftJoin(\App\DAL\Entity\Member::class, 'm', Expr\Join::WITH, 'm.team = t')
            ->groupBy('t')
            ->addGroupBy('l')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
