<?php

namespace App\DAL\Entity;

use App\DAL\Repository\TournamentMatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentMatchRepository::class)]
class TournamentMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateInterval $duration = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\ManyToOne(inversedBy: 'tournamentMatches', targetEntity: self::class)]
    #[ORM\JoinColumn]
    private ?self $childMatch = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    #[ORM\OneToMany(targetEntity: TournamentMatch::class, mappedBy: 'childMatch', cascade: ['persist'])]
    private Collection $tournamentMatches;

    public function __construct()
    {
        $this->tournamentMatches = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDuration(): ?\DateInterval
    {
        return $this->duration;
    }

    public function setDuration(\DateInterval $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getChildMatch(): ?self
    {
        return $this->childMatch;
    }

    public function setChildMatch(?self $childMatch): self
    {
        $this->childMatch = $childMatch;

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

    /**
     * @return Collection<int, TournamentMatch>
     */
    public function getTournamentMatches(): Collection
    {
        return $this->tournamentMatches;
    }

    public function addTournamentMatch(TournamentMatch $tournamentMatch): self
    {
        if (!$this->tournamentMatches->contains($tournamentMatch)) {
            $this->tournamentMatches->add($tournamentMatch);
            $tournamentMatch->setChildMatch($this);
        }

        return $this;
    }

    public function removeTournamentMatch(TournamentMatch $tournamentMatch): self
    {
        if ($this->tournamentMatches->removeElement($tournamentMatch)) {
            // set the owning side to null (unless already changed)
            if ($tournamentMatch->getChildMatch() === $this) {
                $tournamentMatch->setChildMatch(null);
            }
        }

        return $this;
    }
}
