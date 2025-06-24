<?php

namespace App\Helpers;

use App\Models\Balance;

class BeginningBalance {

    public static function debit( $code )
    {
        $period = settings('active_period');
        $year = intval($period) - 1;
        return Balance::where('coa_code', $code)->where('year', $year)->sum('debit');
    }

    public static function credit( $code )
    {
        $period = settings('active_period');
        $year = intval($period) - 1;
        return Balance::where('coa_code', $code)->where('year', $year)->sum('credit');
    }

    public static function sumDebit()
    {
        $period = settings('active_period');
        $year = intval($period) - 1;
        return Balance::where('year', $year)->sum('debit');
    }

    public static function sumCredit()
    {
        $period = settings('active_period');
        $year = intval($period) - 1;
        return Balance::where('year', $year)->sum('credit');
    }
}
