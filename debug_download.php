<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

use App\Models\OrderLabel;

echo "=== Order Label Download Debug ===\n";

// Check if records exist
$orderLabels = OrderLabel::all();
echo "Total records: " . $orderLabels->count() . "\n\n";

foreach ($orderLabels as $orderLabel) {
    echo "ID: " . $orderLabel->id . "\n";
    echo "Code: " . $orderLabel->code . "\n";
    echo "File Path: " . ($orderLabel->file_path ?? 'NULL') . "\n";
    echo "Split Filename: " . ($orderLabel->split_filename ?? 'NULL') . "\n";

    if ($orderLabel->file_path) {
        $filePath = storage_path('app/public/' . $orderLabel->file_path);
        echo "Full Path: " . $filePath . "\n";
        echo "File Exists: " . (file_exists($filePath) ? 'YES' : 'NO') . "\n";

        if (file_exists($filePath)) {
            echo "File Size: " . filesize($filePath) . " bytes\n";
        }
    }

    echo "\n---\n\n";
}
