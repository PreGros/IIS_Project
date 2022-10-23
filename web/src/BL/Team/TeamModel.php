<?php

namespace App\BL\Team;

class TeamModel
{
    private string $name;

    private string $imagePath;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $val)
    {
        $this->name = $val;
    }

    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    public function setImagePath(string $val)
    {
        $this->imagePath = $val;
    }
}
