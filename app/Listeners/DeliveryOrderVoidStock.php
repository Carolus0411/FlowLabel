<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\DeliveryOrderVoided;
use App\Models\InventoryLedger;
use App\Models\DeliveryOrder;

class DeliveryOrderVoidStock
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
    public function handle(DeliveryOrderVoided $event): void
    {
        $deliveryOrder = $event->deliveryOrder;

        // Reload to ensure we have the latest state and relationships are clean
        $deliveryOrder = $deliveryOrder->fresh(['details.serviceCharge.itemType']);

        if (!$deliveryOrder) {
            return;
        }

        foreach ($deliveryOrder->details as $detail) {
            $item = $detail->serviceCharge;
            if ($item && $item->itemType && $item->itemType->is_stock) {
                InventoryLedger::create([
                    'date' => $deliveryOrder->delivery_date,
                    'service_charge_id' => $detail->service_charge_id,
                    'qty' => abs($detail->qty), // Positive to reverse the out transaction
                    'price' => $detail->price,
                    'type' => 'out', // Keep type 'out' to reduce the 'out' sum
                    'reference_type' => DeliveryOrder::class,
                    'reference_id' => $deliveryOrder->id,
                ]);
            }
        }
    }
}
