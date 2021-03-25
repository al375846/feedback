<?php

namespace App\Utils;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;

class ActiveCategories {

    public function __construct(EntityManagerInterface $entitymanager)
    {
        $this->entitymanager = $entitymanager;
        $this->categories = $this->getCategories();
    }

    public function resetCAtegories() {
        $this->categories = $this->getCategories();
    }

    public function buildActiveCategories(): array {
        $categories = [];
        foreach($this->categories as $category) {
            $cat = $this->entitymanager->getRepository(Category::class)->find($category['id']);
            $newcat['id'] = $category['id'];
            $newcat['name'] = $cat->getName();
            $newcat['description'] = $cat->getDescription();
            $categories[] = $newcat;
        }
        return $categories;
    }


    private function getCategories(): array
    {
        $conn = $this->entitymanager->getConnection();
        $sql = "SELECT category_id as id, COUNT(id) FROM publication GROUP BY category_id ORDER BY 2 DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAllAssociative();
    }
}

