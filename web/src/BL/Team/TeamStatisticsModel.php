<?php

namespace App\BL\Team;

class TeamStatisticsModel
{
    private int $tournamentCount;

    private int $wonTournaments;

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
}
