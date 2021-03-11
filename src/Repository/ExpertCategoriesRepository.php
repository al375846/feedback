<?php

namespace App\Repository;

use App\Entity\ExpertCategories;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExpertCategories|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpertCategories|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpertCategories[]    findAll()
 * @method ExpertCategories[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpertCategoriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpertCategories::class);
    }

    // /**
    //  * @return ExpertCategories[] Returns an array of ExpertCategories objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ExpertCategories
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
