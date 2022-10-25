<?php

namespace App\BL\Team;

class TeamTableModel
{
    private string $id;

    private string $name;

    private string $leaderNickName;

    private int $memberCount;

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

    public function getLeaderNickName(): string
    {
        return $this->leaderNickName;
    }

    public function setLeaderNickName(string $val)
    {
        $this->leaderNickName = $val;
    }

    public function getMemberCount(): int
    {
        return $this->memberCount;
    }

    public function setMemberCount(int $val)
    {
        $this->memberCount = $val;
    }
}
