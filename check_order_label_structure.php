<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== Order Label Table Structure ===\n\n";

echo "Columns:\n";
$columns = Schema::getColumnListing('order_label');
foreach ($columns as $column) {
    echo "  - $column\n";
}

echo "\n\nSample data (first record):\n";
$sample = DB::table('order_label')->first();
if ($sample) {
    foreach ($sample as $key => $value) {
        echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
    }
} else {
    echo "  No data found\n";
}
