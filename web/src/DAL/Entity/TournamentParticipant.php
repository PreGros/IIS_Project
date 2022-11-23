<?php

namespace App\DAL\Entity;

use App\DAL\Repository\TournamentParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentParticipantRepository::class)]
class TournamentParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $approved = null;

    #[ORM\ManyToOne]
    private ?Team $signedUpTeam = null;

    #[ORM\ManyToOne]
    private ?User $signedUpUser = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApproved(): ?bool
    {
        return $this->approved;
    }

    public function setApproved(bool $approved): self
    {
        $this->approved = $approved;

        return $this;
    }

    public function getSignedUpTeam(): ?Team
    {
        return $this->signedUpTeam;
    }

    public function setSignedUpTeam(?Team $signedUpTeam): self
    {
        $this->signedUpTeam = $signedUpTeam;

        return $this;
    }

    public function getSignedUpUser(): ?User
    {
        return $this->signedUpUser;
    }

    public function setSignedUpUser(?User $signedUpUser): self
    {
        $this->signedUpUser = $signedUpUser;

        return $this;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(?Tournament $tournament): self
    {
        $this->tournament = $tournament;

        return $this;
    }
}
