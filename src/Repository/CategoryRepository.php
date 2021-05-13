<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
    * @return Category[] Returns an array of Category objects
    */
    public function findActiveCategories(): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQueryBuilder()
            ->select("c.id, COUNT(p.id) as rate, c.name")
            ->from("App\Entity\Publication", "p")
            ->leftJoin("p.category", "c")
            ->groupBy("c.id")
            ->orderBy("COUNT(p.id)", 'DESC')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @return Category[] Returns an array of Category objects
     */
    public function findParentCategories(): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQueryBuilder()
            ->select("c")
            ->from("App\Entity\Category", "c")
            ->where("c.parent is null")
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @param $category
     * @return Category[] Returns an array of Category objects
     */
    public function findSubCategories($category): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQueryBuilder()
            ->select("c.id, c.name, c.description")
            ->from("App\Entity\Category", "c")
            ->where("c.parent = :category")
            ->setParameter('category', $category)
            ->getQuery();

        return $query->getResult();
    }


    /*
    public function findOneBySomeField($value): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
