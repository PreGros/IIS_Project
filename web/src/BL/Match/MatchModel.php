<?php

namespace App\BL\Match;

class MatchModel
{
    private int $id;

    private ?int $childId;

    private \DateInterval $duration;

    private \DateTimeInterface $startTime;

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
}
