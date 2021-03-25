<?php

namespace App\Utils;

use App\Entity\Expert;
use Doctrine\ORM\EntityManagerInterface;

class ActiveExperts{

    public function __construct(EntityManagerInterface $entitymanager)
    {
        $this->entitymanager = $entitymanager;
        $this->experts = $this->getExperts();
    }

    public function resetExperts() {
        $this->experts = $this->getExperts();;
    }

    public function buildActiveExperts(): array {
        $activeExperts = [];
        foreach($this->experts as $expert) {
            $exp = $this->entitymanager->getRepository(Expert::class)->find($expert['id']);
            $newexpert['id'] = $expert['id'];
            $newexpert['username'] = $exp->getUsername();
            $activeExperts[] = $newexpert;
        }
        return $activeExperts;
    }


    private function getExperts(): array
    {
        $conn = $this->entitymanager->getConnection();
        $sql = "SELECT DISTINCT expert_id as id, COUNT(id) FROM feedback GROUP BY expert_id ORDER BY 2 DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAllAssociative();
    }
}
