<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\SalesInvoiceVoided;
use App\Models\Coa;
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
        $salesInvoice = $salesInvoiceVoided->salesInvoice;

        $journal = Journal::where('ref_name','SalesInvoice')->where('ref_id', $salesInvoice->code)->first();

        $journal->details()->delete();
        $journal->delete();
    }
}
