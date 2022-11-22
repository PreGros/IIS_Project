<?php

namespace App\BL\Tournament;


class TournamentParticipantTableModel
{
    private int $idOfParticipant;

    private string $nameOfParticipant;

    private bool $isTeam;

    private bool $approved;

    private bool $createdByCurrentUser;

    public function getIdOfParticipant(): int
    {
        return $this->idOfParticipant;
    }

    public function setIdOfParticipant(int $val)
    {
        $this->idOfParticipant = $val;
    }

    public function getNameOfParticipant(): string
    {
        return $this->nameOfParticipant;
    }

    public function setNameOfParticipant(string $val)
    {
        $this->nameOfParticipant = $val;
    }

    public function getIsTeam(): bool
    {
        return $this->isTeam;
    }

    public function setIsTeam(bool $val)
    {
        $this->isTeam = $val;
    }

    public function getApproved(): bool
    {
        return $this->approved;
    }

    public function setApproved(bool $val)
    {
        $this->approved = $val;
    }

    public function getCreatedByCurrentUser(): bool
    {
        return $this->createdByCurrentUser;
    }

    public function setCreatedByCurrentUser(bool $val)
    {
        $this->createdByCurrentUser = $val;
    }
}
