<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\OrderLabel;
use Illuminate\Support\Facades\Storage;

echo "=== Testing ZIP Creation for Batch Download ===\n\n";

// Check temp directory
$tempDir = storage_path('app/temp');
echo "Temp directory: $tempDir\n";

if (!file_exists($tempDir)) {
    echo "Creating temp directory...\n";
    mkdir($tempDir, 0755, true);
}

echo "Temp directory exists: " . (file_exists($tempDir) ? "YES" : "NO") . "\n";
echo "Temp directory is writable: " . (is_writable($tempDir) ? "YES" : "NO") . "\n\n";

// Get a batch to test
$batch = OrderLabel::whereNotNull('batch_no')
    ->select('batch_no')
    ->groupBy('batch_no')
    ->first();

if (!$batch) {
    echo "No batch found in database. Please import some PDFs first.\n";
    exit;
}

echo "Testing with batch: {$batch->batch_no}\n\n";

$orderLabels = OrderLabel::where('batch_no', $batch->batch_no)->get();
echo "Found {$orderLabels->count()} order labels in this batch\n\n";

// Test zip creation
$safeBatchNo = str_replace(['/', '\\', ' '], '-', $batch->batch_no);
$zipFileName = 'batch_' . $safeBatchNo . '_test.zip';
$zipPath = $tempDir . '/' . $zipFileName;

echo "Zip file will be created at: $zipPath\n\n";

$zip = new \ZipArchive();
$filesAdded = 0;

if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
    echo "✓ ZIP file opened successfully\n\n";

    foreach ($orderLabels as $orderLabel) {
        echo "Checking: {$orderLabel->code}\n";
        echo "  File path: {$orderLabel->file_path}\n";

        if ($orderLabel->file_path && Storage::disk('public')->exists($orderLabel->file_path)) {
            $filePath = Storage::disk('public')->path($orderLabel->file_path);
            echo "  Full path: $filePath\n";
            echo "  File exists: " . (file_exists($filePath) ? "YES" : "NO") . "\n";

            if (file_exists($filePath)) {
                $fileName = $orderLabel->split_filename ?? basename($orderLabel->file_path);
                $zip->addFile($filePath, $fileName);
                $filesAdded++;
                echo "  ✓ Added to ZIP as: $fileName\n";
            }
        } else {
            echo "  ✗ File not found in storage\n";
        }
        echo "\n";
    }

    $zip->close();

    echo "\n=== Summary ===\n";
    echo "Files added to ZIP: $filesAdded\n";
    echo "ZIP file created: " . (file_exists($zipPath) ? "YES" : "NO") . "\n";

    if (file_exists($zipPath)) {
        echo "ZIP file size: " . number_format(filesize($zipPath)) . " bytes\n";
        echo "\nTest ZIP created successfully at: $zipPath\n";
        echo "You can delete it manually after testing.\n";
    }

} else {
    echo "✗ Failed to open ZIP file\n";
    echo "Error code: " . $zip->getStatusString() . "\n";

    // Check common issues
    echo "\nPossible issues:\n";
    echo "1. Folder doesn't exist or isn't writable\n";
    echo "2. ZipArchive extension not enabled\n";
    echo "3. Insufficient permissions\n";
}
