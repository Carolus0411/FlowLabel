<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Events\SalesInvoiceDirectVoided;
use App\Models\Journal;

class SalesInvoiceDirectVoidJournal
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
    public function handle(SalesInvoiceDirectVoided $event): void
    {
        DB::transaction(function () use ($event) {

            $salesInvoiceDirect = $event->salesInvoiceDirect;

            $journal = Journal::where('ref_name', class_basename($salesInvoiceDirect))
                ->where('ref_id', $salesInvoiceDirect->code)
                ->first();

            if ($journal) {
                $journal->update([
                    'status' => 'void'
                ]);
            }

        });
    }
}
