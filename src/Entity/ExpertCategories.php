<?php

namespace App\Entity;

use App\Repository\ExpertCategoriesRepository;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=ExpertCategoriesRepository::class)
 */
class ExpertCategories
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Expert::class, inversedBy="favCategories")
     * @ORM\JoinColumn(nullable=false)
     * @OA\Property(ref=@Model(type=Expert::class))
     */
    private $expert;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @ORM\JoinColumn(nullable=false)
     * @OA\Property(ref=@Model(type=Expert::class))
     * @Groups({"fav_categories"})
     */
    private $category;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExpert(): ?Expert
    {
        return $this->expert;
    }

    public function setExpert(?Expert $expert): self
    {
        $this->expert = $expert;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }
}
