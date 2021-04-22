<?php

namespace App\Repository;

use App\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Publication|null find($id, $lockMode = null, $lockVersion = null)
 * @method Publication|null findOneBy(array $criteria, array $orderBy = null)
 * @method Publication[]    findAll()
 * @method Publication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    public function findAllGreaterId($cursor, $filter): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT p 
                    FROM App\Entity\Publication p 
                    JOIN p.category c
                    JOIN p.apprentice a
                    WHERE p.id < :cursor AND
                    (LOWER(c.name) LIKE :filter
                    OR LOWER(p.title) LIKE :filter
                    OR LOWER(a.username) LIKE :filter
                    OR LOWER(p.tags) LIKE :filter)
                    ORDER BY p.id DESC'
        )
            ->setParameter('filter', '%'.$filter.'%')
            ->setParameter('cursor', $cursor);

        return $query->getResult();
    }

    public function findAllGreaterIdByCategory($cursor, $filter, $name, $names): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT p 
                    FROM App\Entity\Publication p 
                    JOIN p.category c
                    JOIN p.apprentice a
                    WHERE p.id < :cursor AND
                    (LOWER(c.name) = :category OR LOWER(c.name) IN (:subcategory))
                    AND (LOWER(p.title) LIKE :filter
                    OR LOWER(a.username) LIKE :filter
                    OR LOWER(p.tags) LIKE :filter)
                    ORDER BY p.id DESC'
        )
        ->setParameter('filter', '%'.$filter.'%')
        ->setParameter('cursor', $cursor)
        ->setParameter('category', $name)
        ->setParameter('subcategory', $names);

        return $query->getResult();
    }

    public function findAllGreaterIdByExpert($cursor, $filter, $names): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT p 
            FROM App\Entity\Publication p 
            JOIN p.category c
            JOIN p.apprentice a
            WHERE p.id < :cursor AND
            LOWER(c.name) IN (:subcategory)
            AND (LOWER(p.title) LIKE :filter
            OR LOWER(a.username) LIKE :filter
            OR LOWER(p.tags) LIKE :filter)
            ORDER BY p.id DESC'
        )
        ->setParameter('filter', '%'.$filter.'%')
        ->setParameter('cursor', $cursor)
        ->setParameter('subcategory', $names);

        return $query->getResult();
    }

    // /**
    //  * @return Publication[] Returns an array of Publication objects
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
    public function findOneBySomeField($value): ?Publication
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
