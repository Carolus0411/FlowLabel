<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking three_pl_id in order_label ===\n\n";

$withThreePl = DB::table('order_label')->whereNotNull('three_pl_id')->count();
$withoutThreePl = DB::table('order_label')->whereNull('three_pl_id')->count();

echo "Orders with three_pl_id: $withThreePl\n";
echo "Orders without three_pl_id: $withoutThreePl\n";

echo "\n=== Platform Breakdown Query Test ===\n\n";

$platforms = DB::table('order_label')
    ->join('three_pl', 'order_label.three_pl_id', '=', 'three_pl.id')
    ->select('three_pl.name as platform', DB::raw('count(*) as count'))
    ->where('order_label.saved', 1)
    ->groupBy('three_pl.name')
    ->orderBy('count', 'desc')
    ->get();

if ($platforms->isEmpty()) {
    echo "No data found with JOIN.\n";
    echo "\nTrying LEFT JOIN...\n\n";

    $platforms = DB::table('order_label')
        ->leftJoin('three_pl', 'order_label.three_pl_id', '=', 'three_pl.id')
        ->select(DB::raw('COALESCE(three_pl.name, \'Unknown\') as platform'), DB::raw('count(*) as count'))
        ->where('order_label.saved', 1)
        ->groupBy('three_pl.name')
        ->orderBy('count', 'desc')
        ->get();
}

foreach ($platforms as $platform) {
    echo "{$platform->platform}: {$platform->count} orders\n";
}

echo "\n=== Sample order_label records ===\n\n";
$samples = DB::table('order_label')
    ->select('id', 'code', 'three_pl_id', 'saved')
    ->limit(3)
    ->get();

foreach ($samples as $sample) {
    echo "ID: {$sample->id} | Code: {$sample->code} | 3PL ID: " . ($sample->three_pl_id ?? 'NULL') . " | Saved: {$sample->saved}\n";
}
