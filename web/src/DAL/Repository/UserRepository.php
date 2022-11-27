<?php

namespace App\DAL\Repository;

use App\DAL\Entity\Team;
use App\DAL\Entity\Tournament;
use App\DAL\Entity\TournamentParticipant;
use App\DAL\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Paginator<User>
     */
    public function findTableData(int $limit, int $start, string $order, bool $ascending, string $search): Paginator
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder
            ->select('u')
            ->from(User::class, 'u')
            ->where($queryBuilder->expr()->orX(
                'u.email LIKE :p_search',
                'u.nickname LIKE :p_search'  
            ))
            ->andWhere('u.isDeactivated = 0');

        if ($order !== ''){
            $queryBuilder
                ->orderBy(
                match($order){
                    'email' => 'u.email',
                    'nickname' => 'u.nickname'
                },
                $ascending ?
                    'ASC' :
                    'DESC'
                );
        }
        
        $query = $queryBuilder
            ->setParameter('p_search', "%{$search}%")
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query, false);
    }

    public function findStatistics(int $id): array
    {   
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        return $queryBuilder
            ->select('COUNT(t.id) as tournamentCount, SUM(case WHEN t.winner = tp THEN 1 ELSE 0 END) as wonTournaments')
            ->addSelect('SUM(case WHEN tp.approved = 1 THEN 1 ELSE 0 END) attendedTournamentCount')
            ->from(Tournament::class, 't')
            ->innerJoin(TournamentParticipant::class, 'tp', Join::WITH, 'tp.tournament = t')
            ->leftJoin(Team::class, 'tm', Join::WITH, $queryBuilder->expr()->andX(
                    'tp.signedUpTeam = tm',
                    $queryBuilder->expr()->orX(
                        'IDENTITY(tm.leader) = :p_userId',
                        'EXISTS (' .
                            $this->getEntityManager()->createQueryBuilder()
                                ->select('m')
                                ->from(\App\DAL\Entity\Member::class, 'm')
                                ->where('IDENTITY(m.team) = tm.id')
                                ->andWhere('IDENTITY(m.user) = :p_userId')
                                ->getDQL()
                        . ')'
                    )
                )
            )
            ->where('tm IS NOT NULL')
            ->orWhere('IDENTITY(tp.signedUpUser) = :p_userId')
            ->setParameter('p_userId', $id)
            ->getQuery()
            ->getSingleResult();

//         SELECT COUNT(t.id), sum(case when t.winner_id = tp.id then 1 else 0 end) FROM tournament as t
// INNER JOIN tournament_participant as tp on tp.tournament_id = t.id and tp.signed_up_user_id = 1
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
