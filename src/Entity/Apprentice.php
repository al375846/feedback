<?php

namespace App\Entity;

use App\Repository\ApprenticeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use App\Entity\User;

/**
 * @ORM\Entity(repositoryClass=ApprenticeRepository::class)
 */
class Apprentice
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=User::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @OA\Property(ref=@Model(type=User::class))
     *
     */
    private $userdata;

    /**
     * @ORM\OneToMany(targetEntity=Publication::class, mappedBy="apprentice")
     */
    private $publications;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     * @OA\Property(type="string", maxLength=180, nullable=true)
     * @Groups({"publications", "feedbacks", "fav_categories", "incidences"})
     */
    private $username;

    public function __construct()
    {
        $this->publications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserdata(): ?User
    {
        return $this->userdata;
    }

    public function setUserdata(User $userdata): self
    {
        $this->userdata = $userdata;

        return $this;
    }

    /**
     * @return Collection|Publication[]
     */
    public function getPublications(): Collection
    {
        return $this->publications;
    }

    public function addPublication(Publication $publication): self
    {
        if (!$this->publications->contains($publication)) {
            $this->publications[] = $publication;
            $publication->setApprentice($this);
        }

        return $this;
    }

    public function removePublication(Publication $publication): self
    {
        if ($this->publications->removeElement($publication)) {
            // set the owning side to null (unless already changed)
            if ($publication->getApprentice() === $this) {
                $publication->setApprentice(null);
            }
        }

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }
}
