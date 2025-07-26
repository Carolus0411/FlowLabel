<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case cash = 'cash';
    case transfer = 'transfer';
    case prepaid = 'prepaid';

    public function color(): string
    {
        return match($this)
        {
            self::cash => 'badge-primary',
            self::transfer => 'badge-success',
            self::prepaid => 'badge-error',
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
