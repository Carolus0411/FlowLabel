<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Correcting Platform Assignment ===\n\n";

// Orders from "Shopee Label.pdf" are Shopee (3 orders)
DB::table('order_label')->whereIn('id', [3, 4, 5])->update(['three_pl_id' => 2]);
echo "✓ Updated ID 3, 4, 5 to Shopee (from Shopee Label.pdf)\n";

// Orders from "ilovepdf_merge1d.pdf" with 18-digit codes are TikTok (2 orders)
DB::table('order_label')->whereIn('id', [1, 2])->update(['three_pl_id' => 3]);
echo "✓ Updated ID 1, 2 to TikTok (18-digit codes)\n";

echo "\n=== Verification ===\n\n";

$distribution = DB::table('order_label')
    ->leftJoin('three_pl', 'order_label.three_pl_id', '=', 'three_pl.id')
    ->select('three_pl.name as platform', DB::raw('count(*) as count'))
    ->where('order_label.saved', 1)
    ->groupBy('three_pl.name')
    ->orderBy('count', 'desc')
    ->get();

foreach ($distribution as $dist) {
    echo "{$dist->platform}: {$dist->count} orders\n";
}

echo "\n✓ Platform data synchronized!\n";
