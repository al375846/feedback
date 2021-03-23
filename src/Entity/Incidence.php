<?php

namespace App\Entity;

use App\Repository\IncidenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

/**
 * @ORM\Entity(repositoryClass=IncidenceRepository::class)
 */
class Incidence
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @OA\Property(type="integer")
     * @Groups({"incidences"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Publication::class)
     * @ORM\JoinColumn(nullable=false)
     * @OA\Property(ref=@Model(type=Publication::class))
     * @Groups({"incidences"})
     */
    private $publication;

    /**
     * @ORM\Column(type="string", length=255)
     * @OA\Property(type="string", maxLength=255)
     * @Groups({"incidences"})
     */
    private $type;

    /**
     * @ORM\Column(type="text")
     * @OA\Property(type="string")
     * @Groups({"incidences"})
     */
    private $description;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): self
    {
        $this->publication = $publication;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
