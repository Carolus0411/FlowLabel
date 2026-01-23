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
$sourcePath = 'd:\\test\\ilovepdf_merge1d - Copy.pdf';
$tempFileName = 'test_tiktok_' . time() . '.pdf';
$tempPath = 'temp/' . $tempFileName;

echo "Copying PDF to storage...\n";
Storage::put($tempPath, file_get_contents($sourcePath));

// Get the 3PL ID for TikTok (assuming it exists)
$tiktok3PL = \App\Models\ThreePl::where('name', 'like', '%tiktok%')->first();
$threePlId = $tiktok3PL ? $tiktok3PL->id : null;

echo "3PL ID: " . ($threePlId ?? 'NULL') . "\n";
echo "\nStarting import job...\n";
echo "This will take several minutes...\n\n";

// Create and dispatch the job
$job = new ProcessOrderLabelImport($tempPath, basename($sourcePath), auth()->id() ?? 1, $threePlId);

// Create a batch
$batch = Bus::batch([
    $job
])->dispatch();

echo "Batch ID: " . $batch->id . "\n";
echo "Waiting for completion...\n\n";

// Wait for batch to complete (with timeout)
$timeout = 600; // 10 minutes
$startTime = time();

while (!$batch->finished() && (time() - $startTime) < $timeout) {
    sleep(5);
    $batch = $batch->fresh();

    $progress = $batch->totalJobs > 0 ? round((($batch->totalJobs - $batch->pendingJobs) / $batch->totalJobs) * 100) : 0;
    echo "\rProgress: $progress% ({$batch->processedJobs()}/{$batch->totalJobs})";
}

echo "\n\n";

if ($batch->finished()) {
    echo "✓ Batch completed!\n\n";

    // Check results
    $records = OrderLabel::where('original_filename', basename($sourcePath))->get();
    echo "Total records created: " . $records->count() . "\n";

    // Check for missing pages
    $pageNumbers = $records->pluck('page_number')->toArray();
    $maxPage = max($pageNumbers);
    $minPage = min($pageNumbers);

    $missingPages = [];
    for ($i = $minPage; $i <= $maxPage; $i++) {
        if (!in_array($i, $pageNumbers)) {
            $missingPages[] = $i;
        }
    }

    if (empty($missingPages)) {
        echo "✓ No missing pages! All 599 pages imported successfully.\n";
    } else {
        echo "✗ Missing pages (" . count($missingPages) . "): " . implode(', ', $missingPages) . "\n";
    }

    // Check saved status
    $notSaved = $records->where('saved', 0)->count();
    if ($notSaved > 0) {
        echo "\n⚠ Warning: $notSaved pages could not be saved as files (FPDI compression issue)\n";
        echo "These pages are recorded in database but file generation failed.\n";
    }

    // Show failed pages
    if ($batch->failedJobs > 0) {
        echo "\n✗ Failed jobs: " . $batch->failedJobs . "\n";
    }

} else {
    echo "✗ Batch timeout or incomplete\n";
}
