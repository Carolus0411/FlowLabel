<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\SalesInvoiceClosed;
use App\Models\InventoryLedger;
use App\Models\SalesInvoice;

class SalesInvoiceUpdateStock
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
    public function handle(SalesInvoiceClosed $event): void
    {
        $salesInvoice = $event->salesInvoice;

        // Reload to ensure we have the latest state and relationships are clean
        $salesInvoice = $salesInvoice->fresh(['details.serviceCharge.itemType']);

        if (!$salesInvoice) {
            return;
        }

        foreach ($salesInvoice->details as $detail) {
            $item = $detail->serviceCharge;
            if ($item && $item->itemType && $item->itemType->is_stock) {
                InventoryLedger::create([
                    'date' => $salesInvoice->invoice_date,
                    'service_charge_id' => $detail->service_charge_id,
                    'qty' => -1 * abs($detail->qty), // Ensure negative
                    'price' => $detail->price,
                    'type' => 'out',
                    'reference_type' => SalesInvoice::class,
                    'reference_id' => $salesInvoice->id,
                ]);
            }
        }
    }
}
