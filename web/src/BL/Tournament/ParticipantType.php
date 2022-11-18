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
        $ret = [];
        foreach (self::cases() as $case){
            $ret[$case->label()] = $case->value;
        }
        return $ret;
    }
}
