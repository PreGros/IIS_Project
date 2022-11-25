<?php

namespace App\BL\Match;

use App\BL\Team\TeamModel;
use App\BL\User\UserModel;

class MatchParticipantModel
{
    private ?int $tournamentPartId;

    private null|UserModel|TeamModel $participant;

    private ?float $points;

    private ?\DateInterval $completionTime;

    public function getTournamentPartId(): ?int
    {
        return $this->tournamentPartId;
    }

    public function setTournamentPartId(?int $val): self
    {
        $this->tournamentPartId = $val;
        return $this;
    }

    public function getParticipant(): null|UserModel|TeamModel
    {
        return $this->participant;
    }

    public function setParticipant(null|UserModel|TeamModel $val): self
    {
        $this->participant = $val;
        return $this;
    }

    public function getParticipantName(): string
    {
        return 
            $this->participant instanceof UserModel ? $this->participant->getNickname() :
            ($this->participant instanceof TeamModel ? $this->participant->getName() : '');
    }

    public function isParticipantTeam(): ?bool
    {
        return 
            $this->participant instanceof TeamModel ? true :
            ($this->participant instanceof UserModel ? false : null);
    }

    public function getParticipantId(): ?int
    {
        return $this->participant?->getId();
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
