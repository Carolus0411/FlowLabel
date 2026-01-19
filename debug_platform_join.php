<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking three_pl table ===\n\n";

$threePls = DB::table('three_pl')->get();

if ($threePls->isEmpty()) {
    echo "❌ three_pl table is EMPTY!\n";
} else {
    echo "✓ Found {$threePls->count()} 3PL records:\n\n";
    foreach ($threePls as $pl) {
        echo "  ID: {$pl->id} | Name: {$pl->name}\n";
    }
}

echo "\n=== Testing JOIN manually ===\n\n";

$result = DB::select("
    SELECT
        three_pl.name as platform,
        COUNT(*) as count
    FROM order_label
    LEFT JOIN three_pl ON order_label.three_pl_id = three_pl.id
    WHERE order_label.saved = 1
    GROUP BY three_pl.name
    ORDER BY count DESC
");

foreach ($result as $row) {
    echo "Platform: " . ($row->platform ?? 'NULL') . " | Count: {$row->count}\n";
}

echo "\n=== Check specific records ===\n\n";

$samples = DB::table('order_label')
    ->leftJoin('three_pl', 'order_label.three_pl_id', '=', 'three_pl.id')
    ->select('order_label.id', 'order_label.three_pl_id', 'three_pl.name as platform_name')
    ->where('order_label.saved', 1)
    ->limit(5)
    ->get();

foreach ($samples as $sample) {
    echo "Order ID: {$sample->id} | 3PL ID: {$sample->three_pl_id} | Platform: " . ($sample->platform_name ?? 'NULL') . "\n";
}
