<?php

namespace App\DAL\Entity;

use App\DAL\Repository\TournamentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 4000, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $participantType = null;

    #[ORM\Column(nullable:true)]
    private ?int $maxTeamMemberCount = null;

    #[ORM\Column(nullable:true)]
    private ?int $minTeamMemberCount = null;

    #[ORM\Column]
    private ?int $maxParticipantCount = null;

    #[ORM\Column]
    private ?int $minParticipantCount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prize = null;

    #[ORM\Column(length: 400)]
    private ?string $venue = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $registrationDateStart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $registrationDateEnd = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $winCondition = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $matchingType = null;

    #[ORM\ManyToOne]
    private ?TournamentType $tournamentType = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne]
    private ?User $approvedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getParticipantType(): ?int
    {
        return $this->participantType;
    }

    public function setParticipantType(int $participantType): self
    {
        $this->participantType = $participantType;

        return $this;
    }

    public function getMaxTeamMemberCount(): ?int
    {
        return $this->maxTeamMemberCount;
    }

    public function setMaxTeamMemberCount(?int $maxTeamMemberCount): self
    {
        $this->maxTeamMemberCount = $maxTeamMemberCount;

        return $this;
    }

    public function getMinTeamMemberCount(): ?int
    {
        return $this->minTeamMemberCount;
    }

    public function setMinTeamMemberCount(?int $minTeamMemberCount): self
    {
        $this->minTeamMemberCount = $minTeamMemberCount;

        return $this;
    }

    public function getMaxParticipantCount(): ?int
    {
        return $this->maxParticipantCount;
    }

    public function setMaxParticipantCount(int $maxParticipantCount): self
    {
        $this->maxParticipantCount = $maxParticipantCount;

        return $this;
    }

    public function getMinParticipantCount(): ?int
    {
        return $this->minParticipantCount;
    }

    public function setMinParticipantCount(int $minParticipantCount): self
    {
        $this->minParticipantCount = $minParticipantCount;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getPrize(): ?string
    {
        return $this->prize;
    }

    public function setPrize(?string $prize): self
    {
        $this->prize = $prize;

        return $this;
    }

    public function getVenue(): ?string
    {
        return $this->venue;
    }

    public function setVenue(string $venue): self
    {
        $this->venue = $venue;

        return $this;
    }

    public function getRegistrationDateStart(): ?\DateTimeInterface
    {
        return $this->registrationDateStart;
    }

    public function setRegistrationDateStart(\DateTimeInterface $registrationDateStart): self
    {
        $this->registrationDateStart = $registrationDateStart;

        return $this;
    }

    public function getRegistrationDateEnd(): ?\DateTimeInterface
    {
        return $this->registrationDateEnd;
    }

    public function setRegistrationDateEnd(\DateTimeInterface $registrationDateEnd): self
    {
        $this->registrationDateEnd = $registrationDateEnd;

        return $this;
    }

    public function getWinCondition(): ?int
    {
        return $this->winCondition;
    }

    public function setWinCondition(?int $winCondition): self
    {
        $this->winCondition = $winCondition;

        return $this;
    }

    public function getMatchingType(): ?int
    {
        return $this->matchingType;
    }

    public function setMatchingType(?int $matchingType): self
    {
        $this->matchingType = $matchingType;

        return $this;
    }

    public function getTournamentType(): ?TournamentType
    {
        return $this->tournamentType;
    }

    public function setTournamentType(?TournamentType $tournamentType): self
    {
        $this->tournamentType = $tournamentType;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): self
    {
        $this->approvedBy = $approvedBy;

        return $this;
    }
}
