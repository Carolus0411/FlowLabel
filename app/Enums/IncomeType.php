<?php

namespace App\Enums;

enum IncomeType: string
{
    case sales = 'sales';
    case non_sales = 'non sales';
    case cash_advance_refund = 'cash advance refund';
    case guarantee_refund = 'guarantee refund';

    public function color(): string
    {
        return match($this)
        {
            self::sales => 'badge-success text-white',
            self::non_sales => 'badge-error text-white',
            self::cash_advance_refund => 'badge-warning',
            self::guarantee_refund => 'badge-info',
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
