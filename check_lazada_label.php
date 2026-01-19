<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\OrderLabel;

// Get latest Lazada label
$label = OrderLabel::where('original_filename', 'like', '%Lazada%')
    ->orWhere('extracted_text', 'like', '%Lazada%')
    ->latest()
    ->first();

if ($label) {
    echo "ID: " . $label->id . "\n";
    echo "Code: " . $label->code . "\n";
    echo "Split Filename: " . $label->split_filename . "\n";
    echo "Original Filename: " . $label->original_filename . "\n";
    echo "\n=== EXTRACTED TEXT ===\n";
    echo $label->extracted_text . "\n";
} else {
    echo "No Lazada label found\n";
}
