<?php

namespace App\Entity;

use App\Repository\OnesignalRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OnesignalRepository::class)
 */
class Onesignal
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $onesignal;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOnesignal(): ?string
    {
        return $this->onesignal;
    }

    public function setOnesignal(string $onesignal): self
    {
        $this->onesignal = $onesignal;

        return $this;
    }
}
