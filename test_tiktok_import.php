<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\ProcessOrderLabelImport;
use App\Models\OrderLabel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Bus;

// Clear old test data
echo "Clearing old test data...\n";
OrderLabel::where('original_filename', 'like', '%ilovepdf_merge1d - Copy.pdf%')->delete();

// Copy test file to storage
$sourcePath = 'd:\\Tes error.pdf';
$tempFileName = 'test_tiktok_' . time() . '.pdf';
$tempPath = 'temp/' . $tempFileName;

echo "Copying PDF to storage...\n";
Storage::put($tempPath, file_get_contents($sourcePath));

// Get the 3PL ID for TikTok (assuming it exists)
$tiktok3PL = \App\Models\ThreePl::find(1); // TikTok ID
$threePlId = $tiktok3PL ? $tiktok3PL->id : null;

echo "3PL ID: " . ($threePlId ?? 'NULL') . "\n";
echo "\nStarting import job...\n";
echo "This will take several minutes...\n\n";

// Create and dispatch the job
$job = new ProcessOrderLabelImport($tempPath, basename($sourcePath), auth()->id() ?? 1, $threePlId);

// Run synchronously instead of batch
echo "Running job synchronously...\n";
$job->handle();
echo "Job completed!\n";
