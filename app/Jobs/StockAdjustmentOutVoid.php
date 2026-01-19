<?php

namespace App\Jobs;

use App\Models\StockAdjustmentOut;
use App\Models\InventoryLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class StockAdjustmentOutVoid implements ShouldQueue
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
            // Delete inventory ledger entry for this stock adjustment
            InventoryLedger::where('reference_type', StockAdjustmentOut::class)
                ->where('reference_id', $this->stockAdjustmentOut->id)
                ->delete();
        });
    }
}
