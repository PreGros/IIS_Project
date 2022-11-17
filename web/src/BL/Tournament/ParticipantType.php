<?php

namespace App\BL\Tournament;

enum ParticipantType: int
{
    case Users = 0;
    case Teams = 1;

    public function label(): string
    {
        return match($this) {
            static::Users => "Users",
            static::Teams => "Teams"
        };
    }

    public static function getTypes(): array
    {
        return [
            'Users' => static::Users,
            'Teams' => static::Teams
        ];
    }
}
