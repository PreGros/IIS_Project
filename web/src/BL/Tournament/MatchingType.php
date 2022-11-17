<?php

namespace App\BL\Tournament;

enum MatchingType: int
{
    case Elimination = 0;
    /** @var int Everyone vs everyone  */
    case AllVsAll = 1;

    public function label(): string
    {
        return match($this) {
            static::Elimination => "Elimination",
            static::AllVsAll => "Everyone vs everyone"
        };
    }

    public static function getTypes(): array
    {
        return [
            'Elimination' => static::Elimination,
            'Everyone vs everyone' => static::AllVsAll
        ];
    }
}
