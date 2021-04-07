<?php

namespace App\Repository;

use App\Entity\Expert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Expert|null find($id, $lockMode = null, $lockVersion = null)
 * @method Expert|null findOneBy(array $criteria, array $orderBy = null)
 * @method Expert[]    findAll()
 * @method Expert[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expert::class);
    }

    /**
    * @return Expert[] Returns an array of Expert objects
    */

    public function findRatedExperts(): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQueryBuilder()
            ->select("e.id, AVG(v.grade) as rate, e.username as name")
            ->from("App\Entity\Valoration", "v")
            ->leftJoin("v.expert", "e")
            ->groupBy("e.id")
            ->orderBy("rate", 'DESC')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @return Expert[] Returns an array of Expert objects
     */

    public function findActiveExperts(): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQueryBuilder()
            ->select("e.id, COUNT(f.id) as rate, e.username as name")
            ->from("App\Entity\Feedback", "f")
            ->leftJoin("f.expert", "e")
            ->groupBy("e.id")
            ->orderBy("COUNT(f.id)", 'DESC')
            ->getQuery();

        return $query->getResult();
    }

    /*
    public function findOneBySomeField($value): ?Expert
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
