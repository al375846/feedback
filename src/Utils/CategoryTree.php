<?php

namespace App\Utils;


use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;

class CategoryTree{

    public function __construct(EntityManagerInterface $entitymanager)
    {
        $this->entitymanager = $entitymanager;
        $this->categories = $this->getCategories();
    }

    public function resetCategories() {
        $this->categories = $this->getCategories();
    }

    public function buildTree(int $parent_id = null): array {
        $subcategories = [];
        foreach($this->categories as $category) {
            $cat = $this->entitymanager->getRepository(Category::class)->findBy(['name'=>$category['name']]);
            $parent = $cat[0]->getParent();
            $id = null;
            if ($parent)
                $id = $parent->getId();
            if($id == $parent_id) {
                $children = $this->buildTree($category['id']);
                if ($children) {
                    $category['children'] = $children;
                }
                $subcategories[] = $category;
            }
        }
        return $subcategories;
    }


    private function getCategories(): array
    {
            $conn = $this->entitymanager->getConnection();
            $sql = "SELECT id, name, description FROM category";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAllAssociative();
    }
}