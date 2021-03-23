<?php

namespace App\Entity;

use App\Repository\ExpertRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=ExpertRepository::class)
 */
class Expert
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
     */
    private $userdata;

    /**
     * @ORM\OneToMany(targetEntity=ExpertCategories::class, mappedBy="expert")
     * @Groups({"fav_categories"})
     */
    private $favCategories;

    /**
     * @ORM\OneToMany(targetEntity=Feedback::class, mappedBy="expert")
     */
    private $feedback;

    /**
     * @ORM\OneToMany(targetEntity=Valoration::class, mappedBy="expert")
     */
    private $valorations;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     * @OA\Property(type="string", maxLength=180, nullable=true)
     * @Groups({"feedbacks", "fav_categories", "publications"})
     */
    private $username;

    public function __construct()
    {
        $this->favCategories = new ArrayCollection();
        $this->feedback = new ArrayCollection();
        $this->valorations = new ArrayCollection();
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
     * @return Collection|ExpertCategories[]
     */
    public function getFavCategories(): Collection
    {
        return $this->favCategories;
    }

    public function addFavCategory(ExpertCategories $favCategory): self
    {
        if (!$this->favCategories->contains($favCategory)) {
            $this->favCategories[] = $favCategory;
            $favCategory->setExpert($this);
        }

        return $this;
    }

    public function removeFavCategory(ExpertCategories $favCategory): self
    {
        if ($this->favCategories->removeElement($favCategory)) {
            // set the owning side to null (unless already changed)
            if ($favCategory->getExpert() === $this) {
                $favCategory->setExpert(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Feedback[]
     */
    public function getFeedback(): Collection
    {
        return $this->feedback;
    }

    public function addFeedback(Feedback $feedback): self
    {
        if (!$this->feedback->contains($feedback)) {
            $this->feedback[] = $feedback;
            $feedback->setExpertf($this);
        }

        return $this;
    }

    public function removeFeedback(Feedback $feedback): self
    {
        if ($this->feedback->removeElement($feedback)) {
            // set the owning side to null (unless already changed)
            if ($feedback->getExpertf() === $this) {
                $feedback->setExpertf(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Valoration[]
     */
    public function getValorations(): Collection
    {
        return $this->valorations;
    }

    public function addValoration(Valoration $valoration): self
    {
        if (!$this->valorations->contains($valoration)) {
            $this->valorations[] = $valoration;
            $valoration->setExpert($this);
        }

        return $this;
    }

    public function removeValoration(Valoration $valoration): self
    {
        if ($this->valorations->removeElement($valoration)) {
            // set the owning side to null (unless already changed)
            if ($valoration->getExpert() === $this) {
                $valoration->setExpert(null);
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
