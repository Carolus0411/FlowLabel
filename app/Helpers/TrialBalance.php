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
        $period = config('settings.accounting_period');

        $coa = Coa::where('code', $code)->first();

        $beginningDebit = BeginningBalance::debit($coa->code);
        $beginningCredit = BeginningBalance::credit($coa->code);

        $journal = JournalDetail::query()
        ->closed()
        ->where('coa_code', 'like', $code . '%')
        ->where('year', '=', $period)
        ->where('month', '<', $period1)
        ->selectRaw(' SUM(debit) as transDebit, SUM(credit) as transCredit ')
        ->first();

        $balance = $debit = $credit = 0;

        $beginning = bcsub($beginningDebit, $beginningCredit, 2);

        if ($coa->normal_balance == 'D') {

            $balance = bcsub($beginningDebit, $beginningCredit, 2);
            $trans = bcsub($journal->transDebit, $journal->transCredit, 2);
            $balance = bcadd($balance, $trans, 2);

            if ($balance > 0) {
                $debit = $balance;
                $credit = 0;
            } else {
                $debit = 0;
                $credit = abs($balance);
            }

        } else {

            $balance = bcsub($beginningCredit, $beginningDebit, 2);
            $trans = bcsub($journal->transCredit, $journal->transDebit, 2);
            $balance = bcadd($balance, $trans, 2);

            if ($balance > 0) {
                $debit = 0;
                $credit = $balance;
            } else {
                $debit = abs($balance);
                $credit = 0;
            }
        }

        $ending = bcsub($debit, $credit, 2);

        return (object) [
            'beginningDebit' => $beginningDebit,
            'beginningCredit' => $beginningCredit,
            'beginning' => $beginning,
            'transDebit' => $journal->transDebit,
            'transCredit' => $journal->transCredit,
            'endingDebit' => $debit,
            'endingCredit' => $credit,
            'ending' => $ending
        ];
    }

    public static function get( $code, $period1, $period2, $cumulative = true )
    {
        $coa = Coa::where('code', $code)->first();

        if ($cumulative) {
            $leftBalance = self::leftBalance( $code, $period1, $period2 );
            $beginningDebit = $leftBalance->endingDebit;
            $beginningCredit = $leftBalance->endingCredit;
        } else {
            $beginningDebit = 0;
            $beginningCredit = 0;
        }


        $journal = JournalDetail::query()
        ->closed()
        ->where('coa_code', 'like', $code . '%')
        ->where('month', '>=', $period1)
        ->where('month', '<=', $period2)
        ->selectRaw(' SUM(debit) as transDebit, SUM(credit) as transCredit ')
        ->first();

        $balance = $debit = $credit = 0;

        $beginning = bcsub($beginningDebit, $beginningCredit, 2);

        if ($coa->normal_balance == 'D') {

            $balance = bcsub($beginningDebit, $beginningCredit, 2);
            $trans = bcsub($journal->transDebit, $journal->transCredit, 2);
            $balance = bcadd($balance, $trans, 2);

            if ($balance > 0) {
                $debit = $balance;
                $credit = 0;
            } else {
                $debit = 0;
                $credit = abs($balance);
            }

        } else {

            $balance = bcsub($beginningCredit, $beginningDebit, 2);
            $trans = bcsub($journal->transCredit, $journal->transDebit, 2);
            $balance = bcadd($balance, $trans, 2);

            if ($balance > 0) {
                $debit = 0;
                $credit = $balance;
            } else {
                $debit = abs($balance);
                $credit = 0;
            }
        }

        $ending = bcsub($debit, $credit, 2);

        return (object) [
            'beginningDebit' => $beginningDebit,
            'beginningCredit' => $beginningCredit,
            'beginning' => $beginning,
            'transDebit' => $journal->transDebit,
            'transCredit' => $journal->transCredit,
            'endingDebit' => $debit,
            'endingCredit' => $credit,
            'ending' => $ending
        ];
    }

    public static function summary( $period1, $period2, $type = '', $cumulative = true )
    {
        $activa = $pasiva = $profitLoss = 0;
        $coa = Coa::query();

        if ($type == 'PL') {
            $coa->where('report_type', 'PL');
        }

        if ($type == 'BS') {
            $coa->where('report_type', 'BS');
        }

        $coas = $coa->with('type')->get();

        foreach ($coas as $coa) {

            $balance = self::get($coa->code, $period1, $period2, $cumulative);

            if ( ! isset($beginningType[$coa->type_id]) ) $beginningType[$coa->type_id] = 0;
            if ( ! isset($transTypeDebit[$coa->type_id]) ) $transTypeDebit[$coa->type_id] = 0;
            if ( ! isset($transTypeCredit[$coa->type_id]) ) $transTypeCredit[$coa->type_id] = 0;
            if ( ! isset($endingType[$coa->type_id]) ) $endingType[$coa->type_id] = 0;

            if ( ! isset($beginningGroup[$coa->group_id]) ) $beginningGroup[$coa->group_id] = 0;
            if ( ! isset($transGroupDebit[$coa->group_id]) ) $transGroupDebit[$coa->group_id] = 0;
            if ( ! isset($transGroupCredit[$coa->group_id]) ) $transGroupCredit[$coa->group_id] = 0;
            if ( ! isset($endingGroup[$coa->group_id]) ) $endingGroup[$coa->group_id] = 0;

            if ( ! isset($beginningSubgroup[$coa->subgroup_id]) ) $beginningSubgroup[$coa->subgroup_id] = 0;
            if ( ! isset($transSubgroupDebit[$coa->subgroup_id]) ) $transSubgroupDebit[$coa->subgroup_id] = 0;
            if ( ! isset($transSubgroupCredit[$coa->subgroup_id]) ) $transSubgroupCredit[$coa->subgroup_id] = 0;
            if ( ! isset($endingSubgroup[$coa->subgroup_id]) ) $endingSubgroup[$coa->subgroup_id] = 0;

            if ( ! isset($sumCoa[$coa->code]) ) $sumCoa[$coa->code] = 0;
            if ( ! isset($sumSubgroup[$coa->subgroup_id]) ) $sumSubgroup[$coa->subgroup_id] = 0;
            if ( ! isset($sumGroup[$coa->group_id]) ) $sumGroup[$coa->group_id] = 0;
            if ( ! isset($sumType[$coa->type_id]) ) $sumType[$coa->type_id] = 0;

            /*if ( $balance->endingDebit > 0 ) {
                $sum = $balance->endingDebit;
            } else {
                $sum = $balance->endingCredit;
            }

            if ( $coa->report_operator == 'MINUS' ) {
                $sum = $sum * -1;
            }

            if ( $coa->report_operator == 'RATE' ) {

                if ( $coa->normal_balance == 'D' ) {
                    if ( $balance->endingCredit > $balance->endingDebit ) {
                        $sum = $sum * -1;
                    }
                }

                if ( $coa->normal_balance == 'C' ) {
                    if ( $balance->endingDebit > $balance->endingCredit ) {
                        $sum = $sum * -1;
                    }
                }
            }*/

            $sum = bcsub($balance->endingDebit, $balance->endingCredit, 2);

            $beginningType[$coa->type_id] = bcadd($beginningType[$coa->type_id], bcsub($balance->beginningDebit, $balance->beginningCredit, 2), 2);
            $transTypeDebit[$coa->type_id] = bcadd($transTypeDebit[$coa->type_id], $balance->transDebit, 2);
            $transTypeCredit[$coa->type_id] = bcadd($transTypeCredit[$coa->type_id], $balance->transCredit, 2);
            $endingType[$coa->type_id] = bcadd($endingType[$coa->type_id], bcsub($balance->endingDebit, $balance->endingCredit, 2), 2);

            $beginningGroup[$coa->group_id] = bcadd($beginningGroup[$coa->group_id], bcsub($balance->beginningDebit, $balance->beginningCredit, 2), 2);
            $transGroupDebit[$coa->group_id] = bcadd($transGroupDebit[$coa->group_id], $balance->transDebit, 2);
            $transGroupCredit[$coa->group_id] = bcadd($transGroupCredit[$coa->group_id], $balance->transCredit, 2);
            $endingGroup[$coa->group_id] = bcadd($endingGroup[$coa->group_id], bcsub($balance->endingDebit, $balance->endingCredit, 2), 2);

            $beginningSubgroup[$coa->subgroup_id] = bcadd($beginningSubgroup[$coa->subgroup_id], bcsub($balance->beginningDebit, $balance->beginningCredit, 2), 2);
            $transSubgroupDebit[$coa->subgroup_id] = bcadd($transSubgroupDebit[$coa->subgroup_id], $balance->transDebit, 2);
            $transSubgroupCredit[$coa->subgroup_id] = bcadd($transSubgroupCredit[$coa->subgroup_id], $balance->transCredit, 2);
            $endingSubgroup[$coa->subgroup_id] = bcadd($endingSubgroup[$coa->subgroup_id], bcsub($balance->endingDebit, $balance->endingCredit, 2), 2);

            $sumCoa[$coa->code] = $sum;
            $sumSubgroup[$coa->subgroup_id] = bcadd($sumSubgroup[$coa->subgroup_id], $sum, 2);
            $sumGroup[$coa->group_id] = bcadd($sumGroup[$coa->group_id], $sum, 2);
            $sumType[$coa->type_id] = bcadd($sumType[$coa->type_id], $sum, 2);

            if ($coa->report_type == 'PL') {
                $profitLoss = bcadd($profitLoss, $sum, 2);
            }

            if ($type == 'BS') {

                if ($coa->type->bs_type == 'Activa') {
                    $activa = bcadd($activa, $sum, 2);
                }

                if ($coa->type->bs_type == 'Pasiva') {
                    $pasiva = bcadd($pasiva, $sum, 2);
                }
            }
        }

        if ($type == 'BS') {
            $current_year_earnings = \App\Models\Coa::query()->where('current_year_earnings', 'true')->first();
            if (!isset($endingGroup[$current_year_earnings->group_id])) $endingGroup[$current_year_earnings->group_id] = 0;
            if (!isset($endingType[$current_year_earnings->type_id])) $endingType[$current_year_earnings->type_id] = 0;

            $summary = self::summary( $period1, $period2, 'PL', $cumulative );
            $newProfitLoss = $summary->profitLoss ?? 0;
            $endingGroup[$current_year_earnings->group_id] = bcadd($endingGroup[$current_year_earnings->group_id], $newProfitLoss, 2);
            $endingType[$current_year_earnings->type_id] = bcadd($endingType[$current_year_earnings->type_id], $newProfitLoss, 2);
            $pasiva = bcadd($pasiva, $newProfitLoss, 2);
        }

        return (object) [
            'beginningType' => $beginningType,
            'transTypeDebit' => $transTypeDebit,
            'transTypeCredit' => $transTypeCredit,
            'endingType' => $endingType,

            'beginningGroup' => $beginningGroup,
            'transGroupDebit' => $transGroupDebit,
            'transGroupCredit' => $transGroupCredit,
            'endingGroup' => $endingGroup,

            'beginningSubgroup' => $beginningSubgroup,
            'transSubgroupDebit' => $transSubgroupDebit,
            'transSubgroupCredit' => $transSubgroupCredit,
            'endingSubgroup' => $endingSubgroup,

            'sumCoa' => $sumCoa,
            'sumSubgroup' => $sumSubgroup,
            'sumGroup' => $sumGroup,
            'sumType' => $sumType,
            'profitLoss' => $profitLoss,
            'activa' => $activa,
            'pasiva' => $pasiva,
        ];
    }
}
