<?php

namespace App\Entity;

use App\Repository\FeedbackRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FeedbackRepository::class)
 */
class Feedback
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Publication::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $publication;

    /**
     * @ORM\ManyToOne(targetEntity=Expert::class, inversedBy="feedback")
     * @ORM\JoinColumn(nullable=false)
     */
    private $expert;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $video;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $document;

    /**
     * @ORM\Column(type="array")
     */
    private $images = [];

    /**
     * @ORM\OneToOne(targetEntity=Valoration::class, mappedBy="feedback", cascade={"persist", "remove"})
     */
    private $valoration;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

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

    public function getExpert(): ?Expert
    {
        return $this->expert;
    }

    public function setExpert(?Expert $expert): self
    {
        $this->expert = $expert;

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

    public function getVideo(): ?string
    {
        return $this->video;
    }

    public function setVideo(string $video): self
    {
        $this->video = $video;

        return $this;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(string $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(array $images): self
    {
        $this->images = $images;

        return $this;
    }

    public function getValoration(): ?Valoration
    {
        return $this->valoration;
    }

    public function setValoration(Valoration $valoration): self
    {
        // set the owning side of the relation if necessary
        if ($valoration->getFeedback() !== $this) {
            $valoration->setFeedback($this);
        }

        $this->valoration = $valoration;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }
}
