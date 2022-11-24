<?php

namespace App\BL\Tournament;

use App\BL\Team\TeamModel;
use App\BL\User\UserModel;

class TournamentParticipantModel
{
    private int $id;

    private null|UserModel|TeamModel $participant;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $val): self
    {
        $this->id = $val;
        return $this;
    }

    public function setParticipant(null|UserModel|TeamModel $participant): self
    {
        $this->participant = $participant;
        return $this;
    }

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

}
