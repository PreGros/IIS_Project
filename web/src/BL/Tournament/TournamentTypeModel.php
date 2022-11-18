<?php

namespace App\BL\Tournament;


class TournamentTypeModel
{
    private int $id;

    private string $name;

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $val)
    {
        $this->name = $val;
    }
}
