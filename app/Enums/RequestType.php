<?php

namespace App\Enums;

enum RequestType: string
{
    case void = 'void';
    case delete = 'delete';

    public function color(): string
    {
        return match($this)
        {
            self::void => 'badge-error text-white',
            self::error => 'badge-error text-white',
        };
    }

    public static function toSelect($placeholder = false): array
    {
        $array = [];
        $index = 0;
        if ($placeholder) {
            $array[$index]['id'] = '';
            $array[$index]['name'] = '-- Status --';
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
