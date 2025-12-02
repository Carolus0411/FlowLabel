<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\BankIn;

class RecalcBankInUsedReceivable extends Command
{
    protected $signature = 'recalc:bankin-used-receivable';
    protected $description = 'Recalculate used_receivable for BankIn based on SalesSettlementSource entries';

    public function handle()
    {
        $this->info('Recalculating BankIn used_receivable...');
        $updated = 0;

        if (!Schema::hasColumn('bank_in', 'used_receivable')) {
            $this->warn('Column used_receivable does not exist in bank_in table; aborting.');
            return 0;
        }

        $bankInCodes = DB::table('sales_settlement_source as s')
            ->join('sales_settlement as ss', 's.sales_settlement_code', '=', 'ss.code')
            ->where('s.settleable_type', '=', 'App\\Models\\BankIn')
            ->where('ss.saved', 1)
            ->where('ss.status', '!=', 'void')
            ->distinct()
            ->pluck('s.settleable_id');

        foreach ($bankInCodes as $code) {
            $bankIn = BankIn::where('code', $code)->first();
            if ($bankIn && isset($bankIn->used_receivable) && !$bankIn->used_receivable) {
                $bankIn->update(['used_receivable' => 1]);
                $updated++;
            }
        }

        $this->info('Updated '.$updated.' BankIn records.');
        return 0;
    }
}
