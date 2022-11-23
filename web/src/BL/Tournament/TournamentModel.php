<?php

namespace App\BL\Tournament;

class TournamentModel
{
    private string $name;

    private ?string $description;

    private ParticipantType $participantType;

    private ?int $maxTeamMemberCount;

    private ?int $minTeamMemberCount;

    private int $maxParticipantCount;

    private int $minParticipantCount;

    private \DateTimeInterface $date;

    private ?string $prize;

    private string $venue;

    private \DateTimeInterface $registrationDateStart;

    private \DateTimeInterface $registrationDateEnd;

    private WinCondition $winCondition;

    private MatchingType $matchingType;
    
    private int $createdById;

    private string $createdByNickName;

    private bool $approved;

    private ?int $currentUserRegistrationState;

    private TournamentTypeModel $tournamentTypeModel;

    // public function __construct()
    // {
    //     $this->date = new \DateTime();
    // }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $val)
    {
        $this->name = $val;
    }

    public function getDescription(): ?string
    {
        return $this->description ?? null;
    }

    public function setDescription(?string $val)
    {
        $this->description = $val;
    }

    public function getParticipantType(bool $int = true): int|ParticipantType
    {
        return $int ? $this->participantType?->value : $this->participantType;
    }

    public function setParticipantType(int|ParticipantType $val)
    {
        $this->participantType = is_int($val) ? ParticipantType::tryFrom($val) : $val;
    }

    public function getMaxTeamMemberCount(): ?int
    {
        return $this->maxTeamMemberCount ?? null;
    }

    public function setMaxTeamMemberCount(?int $val)
    {
        $this->maxTeamMemberCount = $val;
    }

    public function getMinTeamMemberCount(): ?int
    {
        return $this->minTeamMemberCount ?? null;
    }

    public function setMinTeamMemberCount(?int $val)
    {
        $this->minTeamMemberCount = $val;
    }

    public function getMaxParticipantCount(): int
    {
        return $this->maxParticipantCount;
    }

    public function setMaxParticipantCount(int $val)
    {
        $this->maxParticipantCount = $val;
    }

    public function getMinParticipantCount(): int
    {
        return $this->minParticipantCount;
    }

    public function setMinParticipantCount(int $val)
    {
        $this->minParticipantCount = $val;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $val)
    {
        $this->date = $val;
    }

    public function getPrize(): ?string
    {
        return $this->prize ?? null;
    }

    public function setPrize(?string $val)
    {
        $this->prize = $val;
    }

    public function getVenue(): string
    {
        return $this->venue;
    }

    public function setVenue(string $val)
    {
        $this->venue = $val;
    }

    public function getRegistrationDateStart(): \DateTimeInterface
    {
        return $this->registrationDateStart;
    }

    public function setRegistrationDateStart(\DateTimeInterface $val)
    {
        $this->registrationDateStart = $val;
    }

    public function getRegistrationDateEnd(): \DateTimeInterface
    {
        return $this->registrationDateEnd;
    }

    public function setRegistrationDateEnd(\DateTimeInterface $val)
    {
        $this->registrationDateEnd = $val;
    }

    public function getWinCondition(bool $int = true): WinCondition|int
    {
        return $int ? $this->winCondition?->value : $this->winCondition;
    }

    public function setWinCondition(WinCondition|int $val)
    {
        $this->winCondition = is_int($val) ? WinCondition::tryFrom($val) : $val;
    }

    public function getMatchingType(bool $int = true): MatchingType|int
    {
        return $int ? $this->matchingType?->value : $this->matchingType;
    }

    public function setMatchingType(MatchingType|int $val)
    {
        $this->matchingType = is_int($val) ? MatchingType::tryFrom($val) : $val;
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

    public function canRegistrate(): bool
    {
        $now = new \DateTime;
        return (($this->registrationDateStart <= $now) && ($now <= $this->registrationDateEnd) && $this->approved);
    }

    public function setCurrentUserRegistrationState(?int $val): self
    {
        $this->currentUserRegistrationState = $val;
        return $this;
    }

    public function getCurrentUserRegistrationState(): ?int
    {
        return $this->currentUserRegistrationState;
    }

    public function setTournamentTypeModel(?TournamentTypeModel $val): self
    {
        $this->tournamentTypeModel = $val;
        return $this;
    }

    public function getTournamentTypeModel(): ?TournamentTypeModel
    {
        return $this->tournamentTypeModel;
    }
}
