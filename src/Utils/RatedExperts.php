<?php

namespace App\Utils;

use App\Entity\Expert;
use Doctrine\ORM\EntityManagerInterface;

class RatedExperts{

    public function __construct(EntityManagerInterface $entitymanager)
    {
        $this->entitymanager = $entitymanager;
        $this->experts = $this->getExperts();
    }

    public function resetExperts() {
        $this->experts = $this->getExperts();;
    }

    public function buildRatedExperts(): array {
        $ratedExperts = [];
        foreach($this->experts as $expert) {
            $exp = $this->entitymanager->getRepository(Expert::class)->find($expert['id']);
            $expert['username'] = $exp->getUsername();
            $ratedExperts[] = $expert;
        }
        return $ratedExperts;
    }


    private function getExperts(): array
    {
        $conn = $this->entitymanager->getConnection();
        $sql = "SELECT expert_id as id, avg(grade)::numeric(10,2) FROM valoration GROUP BY expert_id ORDER BY 2 DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAllAssociative();
    }
}