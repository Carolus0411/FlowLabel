<?php

namespace App\Enums;

enum IncomeType: string
{
    case sales_income = 'sales income';
    case non_sales_income = 'non sales income';
    case cash_advance_refund = 'cash advance refund';
    case guarantee_refund = 'guarantee refund';

    public function color(): string
    {
        return match($this)
        {
            self::sales_income => 'badge-success text-white',
            self::non_sales_income => 'badge-error text-white',
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
