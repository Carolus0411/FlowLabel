<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\SalesInvoiceDirectClosed;
use App\Models\InventoryLedger;
use App\Models\SalesInvoiceDirect;

class SalesInvoiceDirectUpdateStock
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
    public function handle(SalesInvoiceDirectClosed $event): void
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
                    'qty' => -1 * abs($detail->qty), // Ensure negative
                    'price' => $detail->price,
                    'type' => 'out',
                    'reference_type' => SalesInvoiceDirect::class,
                    'reference_id' => $salesInvoiceDirect->id,
                ]);
            }
        }
    }
}
