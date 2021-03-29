<?php

namespace App\Repository;

use App\Entity\NoActiveUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NoActiveUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method NoActiveUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method NoActiveUser[]    findAll()
 * @method NoActiveUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoActiveUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoActiveUser::class);
    }

    // /**
    //  * @return NoActiveUser[] Returns an array of NoActiveUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NoActiveUser
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
