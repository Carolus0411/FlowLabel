<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Events\CashBookVoided;
use App\Models\Journal;

class CashBookVoidJournal
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
    public function handle(CashBookVoided $cashBookVoided): void
    {
        DB::transaction(function () use ($cashBookVoided) {

            $cashBook = $cashBookVoided->cashBook;

            $journal = Journal::where('ref_name','CashBook')
                ->where('ref_id', $cashBook->code)
                ->first();

            if ($journal) {
                $journal->update([
                    'status' => 'void'
                ]);
            }

        });
    }
}
