<?php

namespace App\BL\User;

class UserStatisticsModel
{
    private int $tournamentCount;

    private int $wonTournaments;

    private int $attendedTournamentCount;

    public function getTournamentCount(): int
    {
        return $this->tournamentCount;
    }

    public function setTournamentCount(?int $tournamentCount)
    {
        $this->tournamentCount = $tournamentCount ?? 0;
    }
    
    public function getWonTournaments(): int
    {
        return $this->wonTournaments;
    }

    public function setWonTournaments(?int $wonTournaments)
    {
        $this->wonTournaments = $wonTournaments ?? 0;
    }

    public function getAttendedTournamentCount(): int
    {
        return $this->attendedTournamentCount;
    }

    public function setAttendedTournamentCount(?int $attendedTournamentCount)
    {
        $this->attendedTournamentCount = $attendedTournamentCount ?? 0;
    }
}
