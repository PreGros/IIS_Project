<?php

namespace App\DAL\Entity;

use App\DAL\Repository\MatchParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatchParticipantRepository::class)]
class MatchParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $points = null;

    #[ORM\Column(nullable: true)]
    private ?\DateInterval $completionTime = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?TournamentMatch $tournamentMatch = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?TournamentParticipant $tournamentParticipant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoints(): ?float
    {
        return $this->points;
    }

    public function setPoints(?float $points): self
    {
        $this->points = $points;

        return $this;
    }

    public function getCompletionTime(): ?\DateInterval
    {
        return $this->completionTime;
    }

    public function setCompletionTime(?\DateInterval $completionTime): self
    {
        $this->completionTime = $completionTime;

        return $this;
    }

    public function getTournamentMatch(): ?TournamentMatch
    {
        return $this->tournamentMatch;
    }

    public function setTournamentMatch(?TournamentMatch $tournamentMatch): self
    {
        $this->tournamentMatch = $tournamentMatch;

        return $this;
    }

    public function getTournamentParticipant(): ?TournamentParticipant
    {
        return $this->tournamentParticipant;
    }

    public function setTournamentParticipant(?TournamentParticipant $tournamentParticipant): self
    {
        $this->tournamentParticipant = $tournamentParticipant;

        return $this;
    }
}
