<?php

namespace App\Enums;

enum InvoiceType: string
{
    case CC = 'CC';
    case LC = 'LC';
    case RC = 'RC';

    public function color(): string
    {
        return match($this)
        {
            self::CC => 'badge-success text-white',
            self::LC => 'badge-primary text-white',
            self::RC => 'badge-error text-white',
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
