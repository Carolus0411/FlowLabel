<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\BeginningBalance;
use App\Models\Coa;
use App\Models\JournalDetail;

class TrialBalance {

    public static function leftBalance( $code, $period1, $period2 )
    {
        $year = substr($period1, 0, 4);
        $period = settings('active_period');

        $beginningDebit = BeginningBalance::debit($year, $code);
        $beginningCredit = BeginningBalance::credit($year, $code);

        $journal = JournalDetail::query()
        ->closed()
        // ->where('type', 'general')
        ->where('coa_code', 'like', $code . '%')
        ->where('year', '=', $year)
        ->where('month', '<', $period1)
        ->selectRaw(' SUM(debit) as transDebit, SUM(credit) as transCredit ')
        ->first();

        $beginning = bcsub($beginningDebit, $beginningCredit, 2);
        $trans = bcsub($journal->transDebit, $journal->transCredit, 2);
        $ending = bcadd($beginning, $trans, 2);

        return (object) [
            'beginning' => $beginning,
            'transDebit' => $journal->transDebit,
            'transCredit' => $journal->transCredit,
            'ending' => $ending
        ];
    }

    public static function get( $code, $period1, $period2, $cumulative = true)
    {
        $year = substr($period1, 0, 4);
        $coa = Coa::where('code', $code)->first();

        if ($cumulative) {
            $leftBalance = self::leftBalance( $code, $period1, $period2 );
            $beginning = $leftBalance->ending;
        } else {
            $beginning = 0;
        }

        $journal = JournalDetail::query()
        ->closed()
        // ->where('type', 'general')
        ->where('coa_code', 'like', $code . '%')
        ->where('year', '=', $year)
        ->where('month', '>=', $period1)
        ->where('month', '<=', $period2)
        ->selectRaw(' SUM(debit) as transDebit, SUM(credit) as transCredit ')
        ->first();

        $trans = bcsub($journal->transDebit, $journal->transCredit, 2);
        $ending = bcadd($beginning, $trans, 2);

        // if ($pole AND !self::pole($coa->normal_balance, $ending)) {
        //     $ending = $ending * -1;
        // }

        return (object) [
            'beginning' => $beginning,
            'transDebit' => $journal->transDebit,
            'transCredit' => $journal->transCredit,
            'ending' => $ending
        ];
    }

    public static function currentYearProfit( $period )
    {
        $coas = Coa::where('report_type', 'PL')->isActive()->get();

        $ending = 0;
        foreach ($coas as $coa)
        {
            $leftBalance = self::leftBalance($coa->code, $period, $period);
            $balance = $leftBalance->ending ?? 0;
            $ending = $ending + $balance;
        }

        return $ending;
    }

    public static function currentMonthProfit( $period )
    {
        $coas = Coa::where('report_type', 'PL')->isActive()->get();

        $ending = 0;
        foreach ($coas as $coa)
        {
            $get = self::get($coa->code, $period, $period, FALSE);
            $balance = $get->ending ?? 0;
            $ending = $ending + $balance;
        }

        return $ending;
    }
}
