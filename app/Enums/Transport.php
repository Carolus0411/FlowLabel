<?php

namespace App\Enums;

enum Transport: string
{
    case air = 'air';
    case sea = 'sea';

    public function color(): string
    {
        return match($this)
        {
            self::air => 'badge-success text-white',
            self::sea => 'badge-error text-white',
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
