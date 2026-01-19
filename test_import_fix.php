<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

use App\Jobs\ProcessOrderLabelImport;
use Illuminate\Support\Facades\Storage;

echo "=== Testing PDF Import Fix ===\n";

// Copy the PDF to the import-temp directory
$sourcePdf = 'd:\test\ilovepdf_merge1d - Copy.pdf';
if (!file_exists($sourcePdf)) {
    die("PDF not found at: $sourcePdf\n");
}

$destPath = 'import-temp/test_import_' . time() . '_ilovepdf_merge1d.pdf';
Storage::put($destPath, file_get_contents($sourcePdf));

echo "PDF copied to storage: $destPath\n";

// Process the import
$job = new ProcessOrderLabelImport($destPath, 'ilovepdf_merge1d - Copy.pdf', 1);

echo "Starting import process...\n";
$startTime = microtime(true);

try {
    $job->handle();
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "\n=== Import Complete ===\n";
    echo "Duration: {$duration} seconds\n";
    
    $count = App\Models\OrderLabel::count();
    echo "Total records created: $count\n";
    
    // Show some samples
    echo "\nSample records:\n";
    $samples = App\Models\OrderLabel::orderBy('page_number')->limit(10)->get(['code', 'page_number', 'split_filename']);
    foreach ($samples as $sample) {
        echo "  Page {$sample->page_number}: Code={$sample->code}, File={$sample->split_filename}\n";
    }
    
    echo "\nLast 5 records:\n";
    $lastRecords = App\Models\OrderLabel::orderBy('page_number', 'desc')->limit(5)->get(['code', 'page_number', 'split_filename']);
    foreach ($lastRecords as $record) {
        echo "  Page {$record->page_number}: Code={$record->code}, File={$record->split_filename}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
