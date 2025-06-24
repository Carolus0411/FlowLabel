<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Helpers\BeginningBalance;
use App\Models\Coa;
use App\Models\JournalDetail;

class ProfitLoss {

    public static function get( $date1, $date2 )
    {
        $coas = Coa::where('report_type', 'PL')->get();

        foreach ($coas as $coa) {

            $balance = TrialBalance::get($coa->code, $date1, $date2);

            if ( ! isset($sumCoa[$coa->code]) ) $sumCoa[$coa->code] = 0;
            if ( ! isset($sumSubgroup[$coa->subgroup_id]) ) $sumSubgroup[$coa->subgroup_id] = 0;
            if ( ! isset($sumGroup[$coa->group_id]) ) $sumGroup[$coa->group_id] = 0;
            if ( ! isset($profitLoss) ) $profitLoss = 0;

            if ( $balance->endingDebit > 0 ) {
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
            }

            $sumCoa[$coa->code] = $sum;
            $sumSubgroup[$coa->subgroup_id] = $sumSubgroup[$coa->subgroup_id] + $sum;
            $sumGroup[$coa->group_id] = $sumGroup[$coa->group_id] + $sum;
            $profitLoss = $profitLoss + $sum;
        }

        return (object) [
            'sumCoa' => $sumCoa,
            'sumSubgroup' => $sumSubgroup,
            'sumGroup' => $sumGroup,
            'profitLoss' => $profitLoss
        ];
    }
}
