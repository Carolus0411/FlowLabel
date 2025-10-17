<?php

namespace App\Enums;

enum RequestStatus: string
{
    case open = 'open';
    case approved = 'approved';
    case rejected = 'rejected';

    public function color(): string
    {
        return match($this)
        {
            self::open => 'badge-primary',
            self::approved => 'badge-success',
            self::rejected => 'badge-error',
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
