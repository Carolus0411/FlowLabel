<?php

namespace App\Jobs;

use App\Models\StockAdjustmentOut;
use App\Models\InventoryLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class StockAdjustmentOutApprove implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public StockAdjustmentOut $stockAdjustmentOut,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            // Create inventory ledger entries for each detail item
            foreach ($this->stockAdjustmentOut->details as $detail) {
                InventoryLedger::create([
                    'date' => $this->stockAdjustmentOut->date,
                    'service_charge_id' => $detail->service_charge_id,
                    'qty' => $detail->qty,
                    'price' => $detail->price,
                    'type' => 'out',
                    'transaction_source' => 'Stock Adjustment Out',
                    'reference_number' => $this->stockAdjustmentOut->code,
                    'reference_type' => StockAdjustmentOut::class,
                    'reference_id' => $this->stockAdjustmentOut->id,
                ]);
            }
        });
    }
}
