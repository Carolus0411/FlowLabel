<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Total OrderLabel count: " . App\Models\OrderLabel::count() . "\n";

// Show all columns from first record
echo "\nAll columns from first record:\n";
$first = App\Models\OrderLabel::first();
if ($first) {
    foreach ($first->getAttributes() as $key => $value) {
        echo "{$key}: {$value}\n";
    }
} else {
    echo "No records found\n";
}
