<?php

namespace App\Jobs;

use App\Models\OtherPayableSettlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class OtherPayableSettlementDelete implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public OtherPayableSettlement $otherPayableSettlement,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {

            $this->otherPayableSettlement->details()->delete();
            $this->otherPayableSettlement->delete();

        });
    }
}
