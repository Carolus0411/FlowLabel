<?php

namespace App\Enums;

enum ServiceType: string
{
    case export = 'export';
    case import = 'import';

    public function color(): string
    {
        return match($this)
        {
            self::export => 'badge-success text-white',
            self::import => 'badge-error text-white',
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
