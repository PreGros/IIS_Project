<?php

namespace App\BL\Match;

use App\BL\Util\DateTimeUtil;

class MatchModel
{
    private int $id;

    private ?MatchModel $child;

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

    public function getChild(): ?MatchModel
    {
        return $this->child;
    }

    public function setChild(?MatchModel $val): self
    {
        $this->child = $val;
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

    public function hasStarted() : bool
    {
        return $this->startTime < new \DateTime();
    }

    public function hasEnded(): bool
    {
        return (new \DateTime())->getTimestamp() > ($this->startTime->getTimestamp() + DateTimeUtil::dateIntervalToSeconds($this->duration));
    }

    public function childMatchStarted() : bool
    {
        return ($this->child === null) ? false : $this->child->getStartTime() < new \DateTime();
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
