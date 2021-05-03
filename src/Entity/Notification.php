<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotificationRepository::class)
 */
class Notification
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\Column(type="array")
     */
    private $playerids = [];

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $sent;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $username;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getPlayerids(): ?array
    {
        return $this->playerids;
    }

    public function setPlayerids(array $playerids): self
    {
        $this->playerids = $playerids;

        return $this;
    }

    public function getSent(): ?bool
    {
        return $this->sent;
    }

    public function setSent(?bool $sent): self
    {
        $this->sent = $sent;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }
}
