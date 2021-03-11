<?php

namespace App\Repository;

use App\Entity\Valoration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Valoration|null find($id, $lockMode = null, $lockVersion = null)
 * @method Valoration|null findOneBy(array $criteria, array $orderBy = null)
 * @method Valoration[]    findAll()
 * @method Valoration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ValorationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Valoration::class);
    }

    // /**
    //  * @return Valoration[] Returns an array of Valoration objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Valoration
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
