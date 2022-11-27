<?php

namespace App\BL\Team;

class TeamBasicTableModel
{
    private string $id;

    private string $name;

    private bool $isUserLeader;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $val)
    {
        $this->id = $val;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $val)
    {
        $this->name = $val;
    }

    public function isUserLeader(): bool
    {
        return $this->isUserLeader;
    }

    public function setIsUserLeader(bool $val)
    {
        $this->isUserLeader = $val;
    }
}
