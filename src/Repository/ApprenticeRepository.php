<?php

namespace App\Repository;

use App\Entity\Apprentice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Apprentice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Apprentice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Apprentice[]    findAll()
 * @method Apprentice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApprenticeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Apprentice::class);
    }

    // /**
    //  * @return Apprentice[] Returns an array of Apprentice objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Apprentice
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
