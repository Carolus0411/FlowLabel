<?php

namespace App\Enums;

enum Status: string
{
    case open = 'open';
    case close = 'close';
    case void = 'void';

    public function color(): string
    {
        return match($this)
        {
            self::open => 'badge-primary',
            self::close => 'badge-success',
            self::void => 'badge-error',
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
