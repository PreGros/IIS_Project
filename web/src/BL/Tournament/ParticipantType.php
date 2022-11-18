<?php

namespace App\BL\Tournament;

use App\BL\Util\StringUtil;

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

    public static function getByName(string $name): array
    {
        $ret = [];
        $name = StringUtil::shave($name);
        foreach (self::cases() as $case){
            if (stripos($case->label(), $name) !== false){
                $ret[] = $case->value;
            }
        }
        return $ret;
    }
}
