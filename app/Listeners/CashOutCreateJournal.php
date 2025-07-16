<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Events\CashOutClosed;
use App\Helpers\Code;
use App\Models\Coa;
use App\Models\Journal;
use App\Models\CashOut;

class CashOutCreateJournal
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
    public function handle(CashOutClosed $cashOutClosed): void
    {
        DB::transaction(function () use ($cashOutClosed) {

            $cashOut = $cashOutClosed->cashOut;
            $cashOut->load(['details.coa','details.currency']);

            $code = Code::auto('JV', $cashOut->date);

            // cash out status
            $cashOut->update([
                'status' => 'close'
            ]);

            // Journal header
            $journal = Journal::create([
                'code' => $code,
                'date' => $cashOut->date,
                'note' => $cashOut->note,
                'type' => 'general',
                'debit_total' => $cashOut->total_amount,
                'credit_total' => $cashOut->total_amount,
                'contact_id' => $cashOut->contact_id,
                'ref_name' => class_basename($cashOut),
                'ref_id' => $cashOut->code,
                'status' => 'close',
                'saved' => '1',
            ]);

            // Cash Account
            $journal->details()->create([
                'coa_code' => $cashOut->cashAccount->coa->code,
                'description' => $cashOut->note,
                'dc' => 'D',
                'debit' => 0,
                'credit' => $cashOut->total_amount,
                'date' => $cashOut->date,
            ]);

            // Details
            foreach ($cashOut->details as $detail) {

                $journal->details()->create([
                    'coa_code' => $detail->coa->code,
                    'description' => $cashOut->note,
                    'dc' => 'C',
                    'debit' => $detail->amount,
                    'credit' => 0,
                    'date' => $cashOut->date,
                ]);
            }

        });
    }
}
