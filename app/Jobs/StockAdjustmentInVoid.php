<?php

namespace App\Jobs;

use App\Models\StockAdjustmentIn;
use App\Models\InventoryLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class StockAdjustmentInVoid implements ShouldQueue
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
            // Delete inventory ledger entry for this stock adjustment
            InventoryLedger::where('reference_type', StockAdjustmentIn::class)
                ->where('reference_id', $this->stockAdjustmentIn->id)
                ->delete();
        });
    }
}
