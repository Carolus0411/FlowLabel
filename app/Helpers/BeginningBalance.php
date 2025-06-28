<?php

namespace App\Helpers;

use App\Models\Balance;

class BeginningBalance {

    public static function debit( $year, $code )
    {
        $year = intval($year) - 1;
        return Balance::where('coa_code', 'like', $code . '%')->where('year', $year)->sum('debit');
    }

    public static function credit( $year, $code )
    {
        $year = intval($year) - 1;
        return Balance::where('coa_code', 'like', $code . '%')->where('year', $year)->sum('credit');
    }

    public static function sumDebit( $year )
    {
        $year = intval($year) - 1;
        return Balance::where('coa_code', 'like', $code . '%')->sum('debit');
    }

    public static function sumCredit( $year )
    {
        $year = intval($year) - 1;
        return Balance::where('coa_code', 'like', $code . '%')->sum('credit');
    }
}
