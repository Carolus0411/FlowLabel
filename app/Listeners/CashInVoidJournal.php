<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Events\CashInVoided;
use App\Models\Journal;

class CashInVoidJournal
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
    public function handle(CashInVoided $cashInVoided): void
    {
        DB::transaction(function () use ($cashInVoided) {

            $cashIn = $cashInVoided->cashIn;

            $journal = Journal::where('ref_name', class_basename($cashIn))
                ->where('ref_id', $cashIn->code)
                ->first();

            if ($journal) {
                $journal->update([
                    'status' => 'void'
                ]);
            }

        });
    }
}
