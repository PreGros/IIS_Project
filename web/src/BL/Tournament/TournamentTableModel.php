<?php

namespace App\BL\Tournament;


class TournamentTableModel
{
    private int $id;

    private string $name;

    private ParticipantType $participantType;

    private int $maxParticipantCount;

    private \DateTimeInterface $date;

    private int $createdById;

    private string $createdByNickName;

    private bool $approved;

    private bool $createdByCurrentUser;
    // private ?string $approvedByNickName;

    // private ?int $approvedById;

    // public function __construct()
    // {
    //     $this->date = new \DateTime();
    // }

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

    public function getParticipantType(bool $int = true): int|ParticipantType
    {
        return $int ? $this->participantType?->value : $this->participantType;
    }

    public function setParticipantType(int|ParticipantType $val)
    {
        $this->participantType = is_int($val) ? ParticipantType::tryFrom($val) : $val;
    }

    public function getMaxParticipantCount(): int
    {
        return $this->maxParticipantCount;
    }

    public function setMaxParticipantCount(int $val)
    {
        $this->maxParticipantCount = $val;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $val)
    {
        $this->date = $val;
    }

    public function getCreatedById(): int
    {
        return $this->createdById;
    }

    public function setCreatedById(int $val)
    {
        $this->createdById = $val;
    }

    public function getCreatedByNickName(): string
    {
        return $this->createdByNickName;
    }

    public function setCreatedByNickName(string $val)
    {
        $this->createdByNickName = $val;
    }

    public function getApproved(): bool
    {
        return $this->approved;
    }

    public function setApproved(bool $val)
    {
        $this->approved = $val;
    }

    public function getCreatedByCurrentUser(): bool
    {
        return $this->createdByCurrentUser;
    }

    public function setCreatedByCurrentUser(bool $val)
    {
        $this->createdByCurrentUser = $val;
    }

    // public function getApprovedById(): ?int
    // {
    //     return $this->approvedById;
    // }

    // public function setApprovedById(?int $val)
    // {
    //     $this->approvedById = $val;
    // }

    // public function getApprovedByNickName(): ?string
    // {
    //     return $this->approvedByNickName;
    // }

    // public function setApprovedByNickName(?string $val)
    // {
    //     $this->approvedByNickName = $val;
    // }
}
