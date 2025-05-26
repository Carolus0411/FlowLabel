<?php

namespace App\Enums;

enum ActiveStatus: string
{
    case active = 'active';
    case inactive = 'inactive';

    public function color(): string
    {
        return match($this)
        {
            self::active => 'badge-success text-white',
            self::inactive => 'badge-error text-white',
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
            $array[$index]['name'] = $case->name;
            $index++;
        }
        return $array;
    }
}
