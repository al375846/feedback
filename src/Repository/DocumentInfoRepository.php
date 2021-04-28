<?php

namespace App\Repository;

use App\Entity\DocumentInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DocumentInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentInfo[]    findAll()
 * @method DocumentInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentInfo::class);
    }

    // /**
    //  * @return DocumentInfo[] Returns an array of DocumentInfo objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DocumentInfo
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
