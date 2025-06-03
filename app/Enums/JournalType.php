<?php

namespace App\Enums;

enum JournalType: string
{
    case general = 'general';
    case adjustment = 'adjustment';

    public function color(): string
    {
        return match($this)
        {
            self::general => 'badge-success text-white',
            self::adjustment => 'badge-error text-white',
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
