<?php

namespace App\DAL\Repository;

use App\DAL\Entity\Tournament;
use App\DAL\Entity\TournamentParticipant;
use App\DAL\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournament>
 *
 * @method Tournament|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tournament|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tournament[]    findAll()
 * @method Tournament[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    public function save(Tournament $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Tournament $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Paginator<array<<Tournament|int>>
     */
    public function findTableData(?int $currUserId, int $limit, int $start, string $order, bool $ascending, string $search, array $participantTypes): Paginator
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder
            ->select('t tournament')
            ->addSelect('CASE WHEN (t.approvedBy IS NOT NULL) THEN 1 ELSE 0 END as approved')
            ->addSelect('tp.approved as approved_participant')
            ->from(Tournament::class, 't')
            ->leftJoin(User::class, 'c', Join::WITH, 't.createdBy = c')
            ->leftJoin(TournamentParticipant::class, 'tp', Join::WITH,
                $this->getEntityManager()->createQueryBuilder()->expr()->andX(
                    'IDENTITY(tp.tournament) = t.id',
                    $this->getEntityManager()->createQueryBuilder()->expr()->orX(
                        'IDENTITY(tp.signedUpUser) = :p_user_id',
                        'EXISTS(' .
                        ($qb = $this->getEntityManager()->createQueryBuilder())
                            ->select('tm.id')
                            ->from(\App\DAL\Entity\Team::class, 'tm')
                            ->where('tm.id = IDENTITY(tp.signedUpTeam)')
                            ->andWhere($qb->expr()->orX(
                                'IDENTITY(tm.leader) = :p_user_id',
                                'EXISTS(' .
                                    $this->getEntityManager()->createQueryBuilder()
                                        ->select('m')
                                        ->from(\App\DAL\Entity\Member::class, 'm')
                                        ->where('IDENTITY(m.team) = tm.id')
                                        ->andWhere('IDENTITY(m.user) = :p_user_id')
                                        ->getDQL()
                                . ')')
                        )
                        ->getDQL()
                    . ')'
                    )
                )
            )
            ->where('t.name LIKE :p_search')
            ->orWhere('t.participantType IN (:p_types)')
            ->orWhere('c.nickname LIKE :p_search');

        if ($order !== ''){
            $queryBuilder
                ->orderBy(
                match($order){
                    'name' => 't.name',
                    'date' => 't.date',
                    'participantType' => 't.participantType',
                    'createdByNickName' => 'c.nickname',
                    'isApproved' => 'approved',
                    'isCurrRegistered' => 'approved_participant'
                },
                $ascending ?
                    'ASC' :
                    'DESC'
                );
        }
        
        $query = $queryBuilder
            ->setParameter('p_search', "%{$search}%")
            ->setParameter('p_types', $participantTypes)
            ->setParameter('p_user_id', $currUserId)
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query, false);
    }

    public function findInfo(int $id, int $userId): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('t tournament')
            ->addSelect('tp.approved as approved_participant')
            ->from(Tournament::class, 't')
            ->leftJoin(User::class, 'c', Join::WITH, 't.createdBy = c')
            ->leftJoin(TournamentParticipant::class, 'tp', Join::WITH,
                $this->getEntityManager()->createQueryBuilder()->expr()->andX(
                    'IDENTITY(tp.tournament) = t.id',
                    $this->getEntityManager()->createQueryBuilder()->expr()->orX(
                        'IDENTITY(tp.signedUpUser) = :p_user_id',
                        'EXISTS(' .
                        ($qb = $this->getEntityManager()->createQueryBuilder())
                            ->select('tm.id')
                            ->from(\App\DAL\Entity\Team::class, 'tm')
                            ->where('tm.id = IDENTITY(tp.signedUpTeam)')
                            ->andWhere($qb->expr()->orX(
                                'IDENTITY(tm.leader) = :p_user_id',
                                'EXISTS(' .
                                    $this->getEntityManager()->createQueryBuilder()
                                        ->select('m')
                                        ->from(\App\DAL\Entity\Member::class, 'm')
                                        ->where('IDENTITY(m.team) = tm.id')
                                        ->andWhere('IDENTITY(m.user) = :p_user_id')
                                        ->getDQL()
                                . ')')
                        )
                        ->getDQL()
                    . ')'
                    )
                )
            )
            ->where('t.id = :p_id')
            ->setParameter('p_id', $id)
            ->setParameter('p_user_id', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Tournament[] Returns an array of Tournament objects
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

//    public function findOneBySomeField($value): ?Tournament
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
