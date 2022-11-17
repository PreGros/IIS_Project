<?php

namespace App\BL\Tournament;

enum WinCondition: int
{
    case MaxPoints = 0;

    case MinPoints = 1;

    case MaxTime = 2;

    case MinTime = 3;

    public function label(): string
    {
        return match($this) {
            static::MaxPoints => "Maximum points",
            static::MinPoints => "Minimum points",
            static::MaxTime => "Maximum duration",
            static::MinTime => "Minimum duration"
        };
    }

    public static function getTypes(): array
    {
        return [
            'Maximum points' => static::MaxPoints,
            'Minimum points' => static::MinPoints,
            'Maximum duration' => static::MaxTime,
            'Minimum duration' => static::MinTime
        ];
    }
}
