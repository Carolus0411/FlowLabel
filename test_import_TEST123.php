<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\ProcessOrderLabelImport;
use App\Models\OrderLabel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Bus;

$sourcePath = 'd:\\TEST123.pdf';
if (!file_exists($sourcePath)) {
    echo "Source PDF not found at $sourcePath\n";
    exit(1);
}

// Copy to storage
$tempFileName = 'test_import_TEST123_' . time() . '.pdf';
$tempPath = 'temp/' . $tempFileName;

echo "Copying PDF to storage...\n";
Storage::put($tempPath, file_get_contents($sourcePath));

// Find TikTok 3PL
$tiktok3PL = \App\Models\ThreePl::where('name', 'like', '%tiktok%')->first();
$threePlId = $tiktok3PL ? $tiktok3PL->id : null;

echo "3PL ID: " . ($threePlId ?? 'NULL') . "\n";

$job = new ProcessOrderLabelImport($tempPath, basename($sourcePath), auth()->id() ?? 1, $threePlId);

$batch = Bus::batch([$job])->dispatch();

echo "Batch ID: " . $batch->id . "\n";

echo "Waiting for completion (timeout 10800s)...\n";
$timeout = 10800;
$start = time();

while (!$batch->finished() && (time() - $start) < $timeout) {
    sleep(3);
    $batch = $batch->fresh();
    $progress = $batch->totalJobs > 0 ? round((($batch->totalJobs - $batch->pendingJobs) / $batch->totalJobs) * 100) : 0;
    echo "\rProgress: $progress% ({$batch->processedJobs()}/{$batch->totalJobs})";
}

echo "\n";
if ($batch->finished()) {
    echo "Batch finished. Checking created records...\n";
    $records = OrderLabel::where('original_filename', basename($sourcePath))->get();
    echo "Total records: " . $records->count() . "\n";
    foreach ($records as $r) {
        echo "Page {$r->page_number} -> code={$r->code}, split_file={$r->split_filename}, orderNote={$r->note}\n";
    }
} else {
    echo "Batch did not finish in time.\n";
}
