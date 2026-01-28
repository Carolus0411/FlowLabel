<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestProgressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $total = 100;
        Log::info("Starting TestProgressJob...");
        
        for ($i = 1; $i <= $total; $i++) {
            // Simulate work
            usleep(100000); // 0.1 second per percent = 10 seconds total
            Log::info("Job Progress [Test]: {$i}%");
        }
        
        Log::info("TestProgressJob Completed!");
    }
}
