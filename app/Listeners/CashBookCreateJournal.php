<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Events\CashBookClosed;
use App\Helpers\Code;
use App\Models\Coa;
use App\Models\Journal;
use App\Models\CashBook;

class CashBookCreateJournal
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
    public function handle(CashBookClosed $cashBookClosed): void
    {
        DB::transaction(function () use ($cashBookClosed) {

            $cashBook = $cashBookClosed->cashBook;
            $cashBook->load(['details.coa','details.currency']);

            $code = Code::auto('JV', $cashBook->date);

            // Journal header
            $journal = Journal::create([
                'code' => $code,
                'date' => $cashBook->date,
                'note' => $cashBook->note,
                'type' => 'general',
                'debit_total' => $cashBook->total_amount,
                'credit_total' => $cashBook->total_amount,
                'contact_id' => $cashBook->contact_id,
                'ref_name' => class_basename($cashBook),
                'ref_id' => $cashBook->code,
                'status' => 'close',
                'saved' => '1',
            ]);

            if ($cashBook->type == 'in') {
                $dc = 'D';
                $debit = $cashBook->total_amount;
                $credit = 0;
            } else {
                $dc = 'C';
                $debit = 0;
                $credit = $cashBook->total_amount;
            }

            // Cash Account
            $journal->details()->create([
                'coa_code' => $cashBook->cashAccount->coa->code,
                'description' => $cashBook->note,
                'dc' => $dc,
                'debit' => $debit,
                'credit' => $credit,
                'date' => $cashBook->date,
            ]);

            // Details
            foreach ($cashBook->details as $detail) {

                if ($cashBook->type == 'in') {
                    $dc = 'C';
                    $debit = 0;
                    $credit = $detail->amount;
                } else {
                    $dc = 'D';
                    $debit = $detail->amount;
                    $credit = 0;
                }

                $journal->details()->create([
                    'coa_code' => $detail->coa->code,
                    'description' => $detail->note,
                    'dc' => $dc,
                    'debit' => $debit,
                    'credit' => $credit,
                    'date' => $cashBook->date,
                ]);
            }

        });
    }
}
