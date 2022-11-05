<?php

namespace App\BL\User;

class UserMemberModel
{
    private int $id;

    private string $email;

    private string $nickname;

    private bool $isLeader;

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname)
    {
        $this->nickname = $nickname;
    }

    public function isLeader(): bool
    {
        return $this->isLeader;
    }

    public function setIsLeader(bool $isLeader)
    {
        $this->isLeader = $isLeader;
    }
}
