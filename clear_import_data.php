<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrderLabel;

echo "Clearing OLD Import Data\n";
echo "========================\n\n";

// Get latest batch
$latestBatch = OrderLabel::orderBy('created_at', 'desc')->first();

if ($latestBatch) {
    $batchNo = $latestBatch->batch_no;
    echo "Found batch: $batchNo\n";
    echo "Original file: {$latestBatch->original_filename}\n";

    $recordCount = OrderLabel::where('batch_no', $batchNo)->count();
    echo "Total records: $recordCount\n\n";

    // Ask for confirmation
    echo "⚠️  WARNING: This will delete all records from this batch!\n";
    echo "Type 'yes' to confirm deletion: ";

    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);

    if (trim($line) === 'yes') {
        // Delete records
        $deleted = OrderLabel::where('batch_no', $batchNo)->delete();
        echo "\n✓ Deleted $deleted records\n";

        // Delete files
        $batchFolderName = str_replace('/', '-', $batchNo);
        $folderPath = storage_path('app/public/order-label-splits/' . $batchFolderName);

        if (is_dir($folderPath)) {
            // Count files before deletion
            $files = glob($folderPath . '/*');
            $fileCount = count($files);

            // Delete directory and all files
            array_map('unlink', $files);
            rmdir($folderPath);

            echo "✓ Deleted $fileCount files from: $folderPath\n";
        }

        echo "\n✅ Cleanup complete! You can now re-import the file.\n";
        echo "\nNext steps:\n";
        echo "1. Go to: http://labsysflow.test/cp/order-label/import\n";
        echo "2. Select platform: TikTok\n";
        echo "3. Upload the PDF file\n";
        echo "4. Run: php artisan queue:work\n";
    } else {
        echo "\n❌ Deletion cancelled.\n";
    }
} else {
    echo "No batch found to delete.\n";
}
