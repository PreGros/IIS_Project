<?php

namespace App\BL\User;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserModel implements UserInterface, PasswordAuthenticatedUserInterface
{
    private int $id;

    private string $email;

    private string $password;

    private array $roles = [];

    private ?string $nickname;

    private ?string $firstname;

    private ?string $surname;

    private ?string $phoneNumber;

    private bool $isVerified = false;

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname)
    {
        $this->nickname = $nickname;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname ?? null;
    }

    public function setFirstname(?string $firstname)
    {
        $this->firstname = $firstname;
    }

    public function getSurname(): ?string
    {
        return $this->surname ?? null;
    }

    public function setSurname(?string $surname)
    {
        $this->surname = $surname;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber ?? null;
    }

    public function setPhoneNumber(?string $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

     /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified)
    {
        $this->isVerified = $isVerified;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function haveRole(string $roleName): bool
    {
        return in_array($roleName, $this->roles);
    }

    public function isCurrentUser(?int $id) : bool
    {
        return $this->id === $id;
    }
}
