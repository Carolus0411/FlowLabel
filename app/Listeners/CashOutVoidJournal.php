<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Events\CashOutVoided;
use App\Models\Journal;

class CashOutVoidJournal
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
    public function handle(CashOutVoided $cashOutVoided): void
    {
        DB::transaction(function () use ($cashOutVoided) {

            $cashOut = $cashOutVoided->cashOut;

            $cashOut->update([
                'status' => 'void'
            ]);

            $journal = Journal::where('ref_name', class_basename($cashOut))
                ->where('ref_id', $cashOut->code)
                ->first();

            if ($journal) {
                $journal->update([
                    'status' => 'void'
                ]);
            }

        });
    }
}
