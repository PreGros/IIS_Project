<?php

namespace App\BL\Team;

use Symfony\Component\HttpFoundation\File\File;

class TeamModel
{
    private string $id;

    private string $name;

    private string $imagePath;

    private int $membersCount;

    private ?File $image = null;

    private bool $isDeactivated = false;

    public function getId(): int
    {
        return $this->id;
    }

    // public function setId(int $val)
    // {
    //     $this->id = $val;
    // }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $val)
    {
        $this->name = $val;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath ?? null;
    }

    public function setImagePath(string $val)
    {
        $this->imagePath = $val;
    }

    public function getImage(): ?File
    {
        return $this->image;
    }

    public function setImage(?File $val)
    {
        $this->image = $val;
    }

    public function getIsDeactivated() : bool
    {
        return $this->isDeactivated;
    }

    public function setIsDeactivated(bool $isDeactivated) : self
    {
        $this->isDeactivated = $isDeactivated;
        
        return $this;
    }

    public function getMembersCount(): int
    {
        return $this->membersCount;
    }

    public function setMembersCount(int $val): self
    {
        $this->membersCount = $val;
        return $this;
    }
}
