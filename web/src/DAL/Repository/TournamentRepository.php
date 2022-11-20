<?php

namespace App\DAL\Repository;

use App\DAL\Entity\Tournament;
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
    public function findTableData(int $limit, int $start, string $order, bool $ascending, string $search, array $participantTypes): Paginator
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder
            ->select('t tournament')
            ->addSelect('CASE WHEN (t.approvedBy IS NOT NULL) THEN 1 ELSE 0 END as approved')
            ->from(Tournament::class, 't')
            ->leftJoin(User::class, 'c', Join::WITH, 't.createdBy = c')
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
                    'isApproved' => 'approved'
                },
                $ascending ?
                    'ASC' :
                    'DESC'
                );
        }
        
        $query = $queryBuilder
            ->setParameter('p_search', "%{$search}%")
            ->setParameter('p_types', $participantTypes)
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query, false);
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
