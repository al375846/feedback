<?php

namespace App\Entity;

use App\Repository\ValorationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ValorationRepository::class)
 */
class Valoration
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Feedback::class, inversedBy="valoration", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $feedback;

    /**
     * @ORM\ManyToOne(targetEntity=Expert::class, inversedBy="valorations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $expert;

    /**
     * @ORM\ManyToOne(targetEntity=Apprentice::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $apprentice;

    /**
     * @ORM\Column(type="integer")
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
