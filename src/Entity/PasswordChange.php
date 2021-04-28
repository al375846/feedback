<?php

namespace App\Entity;

use App\Repository\PasswordChangeRepository;
use Doctrine\ORM\Mapping as ORM;

class PasswordChange
{

    private $id;
    private $oldPassword;
    private $newPassword;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOldPassword(): ?string
    {
        return $this->oldPassword;
    }

    public function setOldpassword(string $pass): self
    {
        $this->oldPassword = $pass;

        return $this;
    }

    public function getNewpassword(): ?string
    {
        return $this->newPassword;
    }

    public function setNewpassword(string $pass): self
    {
        $this->newPassword = $pass;

        return $this;
    }
}
