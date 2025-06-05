<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Events\SalesInvoiceVoided;
use App\Models\Journal;

class SalesInvoiceUnjournal
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
    public function handle(SalesInvoiceVoided $salesInvoiceVoided): void
    {
        DB::transaction(function () use ($salesInvoiceVoided) {

            $salesInvoice = $salesInvoiceVoided->salesInvoice;

            $journal = Journal::where('ref_name','SalesInvoice')->where('ref_id', $salesInvoice->code)->first();

            if (isset($journal->details)) {
                $journal->details()->delete();
            }

            if ($journal) {
                $journal->delete();
            }

        });
    }
}
