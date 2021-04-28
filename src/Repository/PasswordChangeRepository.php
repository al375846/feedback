<?php

namespace App\Repository;

use App\Entity\PasswordChange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PasswordChange|null find($id, $lockMode = null, $lockVersion = null)
 * @method PasswordChange|null findOneBy(array $criteria, array $orderBy = null)
 * @method PasswordChange[]    findAll()
 * @method PasswordChange[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PasswordChangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordChange::class);
    }

    // /**
    //  * @return PasswordChange[] Returns an array of PasswordChange objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PasswordChange
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
