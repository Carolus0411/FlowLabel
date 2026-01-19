<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$label = App\Models\OrderLabel::first();

if ($label) {
    echo "=== Order Label Details ===\n";
    echo "Code: " . $label->code . "\n";
    echo "Split Filename: " . $label->split_filename . "\n";
    echo "File Path: " . $label->file_path . "\n";
    echo "Batch No: " . $label->batch_no . "\n";

    $fullPath = storage_path('app/public/' . $label->file_path);
    echo "\nFull Path: " . $fullPath . "\n";

    if (file_exists($fullPath)) {
        echo "✓ File exists!\n";
        echo "File size: " . filesize($fullPath) . " bytes\n";
    } else {
        echo "✗ File not found\n";
    }
} else {
    echo "No order labels found\n";
}
