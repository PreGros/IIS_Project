<?php

namespace App\BL\User;

class UserRegisterModel
{
    private string $email;

    private string $nickname;

    private string $firstname;

    private string $surname;

    private string $phoneNumber;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $val)
    {
        $this->email = $val;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $val)
    {
        $this->nickname = $val;
    }

    public function getFirstname(): string
    {
        return $this->firstname ?? null;
    }

    public function setFirstname(string $val)
    {
        $this->firstname = $val;
    }

    public function getSurname(): string
    {
        return $this->surname ?? null;
    }

    public function setSurname(string $val)
    {
        $this->surname = $val;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber ?? null;
    }

    public function setPhoneNumber(string $val)
    {
        $this->phoneNumber = $val;
    }
}
