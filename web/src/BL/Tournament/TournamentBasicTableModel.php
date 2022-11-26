<?php

namespace App\BL\Tournament;


class TournamentBasicTableModel
{
    private int $id;

    private string $name;

    private bool $approved;

    private ?bool $isUserWinner;

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

    public function getApproved(): bool
    {
        return $this->approved;
    }

    public function setApproved(bool $val)
    {
        $this->approved = $val;
    }

    public function isUserWinner(): ?bool
    {
        return $this->isUserWinner;
    }

    public function setIsUserWinner(?bool $val)
    {
        $this->isUserWinner = $val;
    }
}
