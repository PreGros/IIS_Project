<?php

namespace App\BL\Match;

class MatchModel
{
    private int $id;

    private ?int $childId;

    private \DateInterval $duration;

    private \DateTimeInterface $startTime;

    private ?MatchParticipantModel $participant1;

    private ?MatchParticipantModel $participant2;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $val): self
    {
        $this->id = $val;
        return $this;
    }

    public function getChildId(): ?int
    {
        return $this->childId;
    }

    public function setChildId(?int $val): self
    {
        $this->childId = $val;
        return $this;
    }

    public function getDuration(): \DateInterval
    {
        return $this->duration;
    }

    public function setDuration(\DateInterval $val): self
    {
        $this->duration = $val;
        return $this;
    }

    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $val): self
    {
        $this->startTime = $val;
        return $this;
    }

    public function hasEnded(): bool
    {
        return (new \DateTime())->getTimestamp() > ($this->startTime->getTimestamp() + (int)$this->duration->format('%s'));
    }

    public function getParticipant1(): ?MatchParticipantModel
    {
        return $this->participant1 ?? null;
    }

    public function setParticipant1(?MatchParticipantModel $val): self
    {
        $this->participant1 = $val;
        return $this;
    }

    public function getParticipant2(): ?MatchParticipantModel
    {
        return $this->participant2 ?? null;
    }

    public function setParticipant2(?MatchParticipantModel $val): self
    {
        $this->participant2 = $val;
        return $this;
    }
}
