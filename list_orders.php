<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Order List ===\n\n";

$orders = DB::table('order_label')
    ->select('id', 'code', 'original_filename', 'three_pl_id')
    ->where('saved', 1)
    ->orderBy('id')
    ->get();

$shopeeCount = 0;
$tiktokCount = 0;

foreach ($orders as $o) {
    $platform = DB::table('three_pl')->where('id', $o->three_pl_id)->value('name');
    echo "ID: {$o->id} | Platform: " . ($platform ?? 'NULL') . " | Code: {$o->code} | File: {$o->original_filename}\n";
    
    if ($platform === 'Shopee') $shopeeCount++;
    if ($platform === 'TikTok') $tiktokCount++;
}

echo "\n=== Current Count ===\n";
echo "Shopee: $shopeeCount\n";
echo "TikTok: $tiktokCount\n";
echo "\nUser said it should be: 3 Shopee, 2 TikTok\n";
