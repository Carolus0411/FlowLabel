<?php

namespace App\Enums;

enum InOut: string
{
    case in = 'in';
    case out = 'out';

    public function color(): string
    {
        return match($this)
        {
            self::in => 'badge-success text-white',
            self::out => 'badge-error text-white',
        };
    }

    public static function toSelect($placeholder = false): array
    {
        $array = [];
        $index = 0;
        if ($placeholder) {
            $array[$index]['id'] = '';
            $array[$index]['name'] = '-- Select --';
            $index++;
        }
        foreach (self::cases() as $key => $case) {
            $array[$index]['id'] = $case->value;
            $array[$index]['name'] = $case->value;
            $index++;
        }
        return $array;
    }
}
