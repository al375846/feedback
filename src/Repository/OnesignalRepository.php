<?php

namespace App\Repository;

use App\Entity\Onesignal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Onesignal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Onesignal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Onesignal[]    findAll()
 * @method Onesignal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OnesignalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Onesignal::class);
    }

    // /**
    //  * @return Onesignal[] Returns an array of Onesignal objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Onesignal
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
