<?php

namespace App\Entity;

use App\Repository\ValorationRepository;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=ValorationRepository::class)
 */
class Valoration
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @OA\Property(type="integer")
     * @Groups({"valorations", "feedbacks"})
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Feedback::class, inversedBy="valoration", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @OA\Property(ref=@Model(type=Feedback::class))
     * @Groups({"valorations"})
     */
    private $feedback;

    /**
     * @ORM\ManyToOne(targetEntity=Expert::class, inversedBy="valorations")
     * @ORM\JoinColumn(nullable=false)
     * @OA\Property(ref=@Model(type=Expert::class))
     */
    private $expert;

    /**
     * @ORM\ManyToOne(targetEntity=Apprentice::class)
     * @ORM\JoinColumn(nullable=false)
     * @OA\Property(ref=@Model(type=Apprentice::class))
     */
    private $apprentice;

    /**
     * @ORM\Column(type="integer")
     * @OA\Property(type="integer")
     * @Groups({"valorations", "feedbacks"})
     */
    private $grade;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFeedback(): ?Feedback
    {
        return $this->feedback;
    }

    public function setFeedback(Feedback $feedback): self
    {
        $this->feedback = $feedback;

        return $this;
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

    public function getApprentice(): ?Apprentice
    {
        return $this->apprentice;
    }

    public function setApprentice(?Apprentice $apprentice): self
    {
        $this->apprentice = $apprentice;

        return $this;
    }

    public function getGrade(): ?int
    {
        return $this->grade;
    }

    public function setGrade(int $grade): self
    {
        $this->grade = $grade;

        return $this;
    }
}
