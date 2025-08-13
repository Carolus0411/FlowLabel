<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Events\CashInClosed;
use App\Helpers\Code;
use App\Models\Coa;
use App\Models\Journal;
use App\Models\CashIn;

class CashInCreateJournal
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CashInClosed $cashInClosed): void
    {
        DB::transaction(function () use ($cashInClosed) {

            $cashIn = $cashInClosed->cashIn;
            $cashIn->load(['details.coa','details.currency']);

            $code = Code::auto('JV', $cashIn->date);

            // has settlement?
            $has_settlement = 0;
            $accountReceivable = settings('account_receivable_code');
            if ($cashIn->details()->where('coa_code', $accountReceivable)->exists()) {
                $has_settlement = 1;
            }

            // has prepaid?
            $has_prepaid = 0;
            $ARPrepaid = settings('ar_prepaid_code');
            if ($cashIn->details()->where('coa_code', $ARPrepaid)->exists()) {
                $has_prepaid = 1;
            }

            // update cash in status
            $cashIn->update([
                'has_settlement' => $has_settlement,
                'has_prepaid' => $has_prepaid,
                'status' => 'close'
            ]);

            // Journal header
            $journal = Journal::create([
                'code' => $code,
                'date' => $cashIn->date,
                'note' => $cashIn->note,
                'type' => 'general',
                'debit_total' => $cashIn->total_amount,
                'credit_total' => $cashIn->total_amount,
                'contact_id' => $cashIn->contact_id,
                'ref_name' => class_basename($cashIn),
                'ref_id' => $cashIn->code,
                'status' => 'close',
                'saved' => '1',
            ]);

            // Cash Account
            $journal->details()->create([
                'coa_code' => $cashIn->cashAccount->coa->code,
                'description' => $cashIn->note,
                'dc' => 'D',
                'debit' => $cashIn->total_amount,
                'credit' => 0,
                'date' => $cashIn->date,
            ]);

            // Details
            foreach ($cashIn->details as $detail) {

                $journal->details()->create([
                    'coa_code' => $detail->coa->code,
                    'description' => $cashIn->note,
                    'dc' => 'C',
                    'debit' => 0,
                    'credit' => $detail->amount,
                    'date' => $cashIn->date,
                ]);
            }

        });
    }
}
