<?php

namespace App\BL\Match;

use App\BL\Team\TeamModel;
use App\BL\User\UserModel;

class MatchParticipantModel
{
    private null|UserModel|TeamModel $participant;

    private ?float $points;

    private ?\DateInterval $completionTime;

    public function getParticipant(): null|UserModel|TeamModel
    {
        return $this->participant;
    }

    public function getParticipantName(): string
    {
        return 
            $this->participant instanceof UserModel ? $this->participant->getNickname() :
            ($this->participant instanceof TeamModel ? $this->participant->getName() : '');
    }

    public function setParticipant(null|UserModel|TeamModel $val): self
    {
        $this->participant = $val;
        return $this;
    }

    public function getResult(): string
    {
        return 
            $this->points ??
            $this->completionTime?->format('%H:%I:%S') ??
            'Was not entered';
    }

    public function getPoints(): ?float
    {
        return $this->points;
    }

    public function setPoints(?float $val): self
    {
        $this->points = $val;
        return $this;
    }

    public function getCompletionTime(): ?\DateInterval
    {
        return $this->completionTime;
    }

    public function setCompletionTime(?\DateInterval $val): self
    {
        $this->completionTime = $val;
        return $this;
    }
}
