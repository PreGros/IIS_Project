<?php

namespace App\BL\Util;

class DateTimeUtil
{
    /**
     * Converts date interval to seconds
     * @param \DateInterval $dateInterval
     * @return int
     */
    public static function dateIntervalToSeconds(\DateInterval $dateInterval): int
    {
        $reference = new \DateTimeImmutable();
        $endTime = $reference->add($dateInterval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }

    /**
     * Converts seconds to date interval
     * @param int $seconds
     * @return \DateInterval
     */
    public static function secondsToDateInterval(int $seconds): \DateInterval
    {
        $dtF = new \DateTime("@0");
        $dtT = new \DateTime("@$seconds");
        return $dtF->diff($dtT);
    }
}
