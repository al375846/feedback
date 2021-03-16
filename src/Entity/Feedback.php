<?php

namespace App\Entity;

use App\Repository\FeedbackRepository;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use App\Entity\Publication;
use App\Entity\Expert;
use App\Entity\Valoration;

/**
 * @ORM\Entity(repositoryClass=FeedbackRepository::class)
 */
class Feedback
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @OA\Property(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Publication::class)
     * @ORM\JoinColumn(nullable=false)
     * @OA\Property(ref=@Model(type=Publication::class))
     * @Groups({"feedbacks"})
     */
    private $publication;

    /**
     * @ORM\ManyToOne(targetEntity=Expert::class, inversedBy="feedback")
     * @ORM\JoinColumn(nullable=false)
     * @OA\Property(ref=@Model(type=Expert::class))
     */
    private $expert;

    /**
     * @ORM\Column(type="text")
     * @OA\Property(type="string")
     * @Groups({"feedbacks"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @OA\Property(type="string", maxLength=255)
     * @Groups({"feedbacks"})
     */
    private $video;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @OA\Property(type="string", maxLength=255)
     * @Groups({"feedbacks"})
     */
    private $document;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @OA\Property(type="array", @OA\Items(type="string"))
     * @Groups({"feedbacks"})
     */
    private $images = [];

    /**
     * @ORM\OneToOne(targetEntity=Valoration::class, mappedBy="feedback", cascade={"persist", "remove"})
     * @OA\Property(ref=@Model(type=Valoration::class))
     * @Groups({"feedbacks"})
     */
    private $valoration;

    /**
     * @ORM\Column(type="datetime")
     * @OA\Property(type="datetime")
     * @Groups({"feedbacks"})
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
