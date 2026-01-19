<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\SalesInvoiceDirectVoided;
use App\Models\InventoryLedger;
use App\Models\SalesInvoiceDirect;

class SalesInvoiceDirectVoidStock
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
        $salesInvoiceDirect = $event->salesInvoiceDirect;

        // Reload to ensure we have the latest state and relationships are clean
        $salesInvoiceDirect = $salesInvoiceDirect->fresh(['details.serviceCharge.itemType']);

        if (!$salesInvoiceDirect) {
            return;
        }

        foreach ($salesInvoiceDirect->details as $detail) {
            $item = $detail->serviceCharge;
            if ($item && $item->itemType && $item->itemType->is_stock) {
                InventoryLedger::create([
                    'date' => $salesInvoiceDirect->invoice_date,
                    'service_charge_id' => $detail->service_charge_id,
                    'qty' => abs($detail->qty), // Positive to reverse out
                    'price' => $detail->price,
                    'type' => 'out', // Keep type 'out' to reduce the 'out' sum
                    'reference_type' => SalesInvoiceDirect::class,
                    'reference_id' => $salesInvoiceDirect->id,
                ]);
            }
        }
    }
}
