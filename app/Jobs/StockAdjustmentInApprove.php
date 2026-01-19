<?php

namespace App\Jobs;

use App\Models\StockAdjustmentIn;
use App\Models\InventoryLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class StockAdjustmentInApprove implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public StockAdjustmentIn $stockAdjustmentIn,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            // Create inventory ledger entries for each detail item
            foreach ($this->stockAdjustmentIn->details as $detail) {
                InventoryLedger::create([
                    'date' => $this->stockAdjustmentIn->date,
                    'service_charge_id' => $detail->service_charge_id,
                    'qty' => $detail->qty,
                    'price' => $detail->price,
                    'type' => 'in',
                    'transaction_source' => 'Stock Adjustment In',
                    'reference_number' => $this->stockAdjustmentIn->code,
                    'reference_type' => StockAdjustmentIn::class,
                    'reference_id' => $this->stockAdjustmentIn->id,
                ]);
            }
        });
    }
}
