<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Synchronizing Platform Data ===\n\n";

$orders = DB::table('order_label')
    ->select('id', 'code', 'split_filename', 'three_pl_id', 'original_filename')
    ->where('saved', 1)
    ->get();

$updated = 0;

foreach ($orders as $order) {
    $platformId = null;
    $platformName = null;
    
    // Priority 1: Check code pattern first (more reliable than filename)
    // TikTok pattern: 260101T + alphanumeric (TikTok order codes)
    if (preg_match('/^260\d{3}T[A-Z0-9]{7,}$/i', $order->code)) {
        $platformId = 3;
        $platformName = 'TikTok';
    }
    // Shopee pattern: 18-digit numeric code
    elseif (preg_match('/^\d{18}$/', $order->code)) {
        $platformId = 2;
        $platformName = 'Shopee';
    }
    
    // Priority 2: Check original filename if not detected from code
    if (!$platformId) {
        $originalLower = strtolower($order->original_filename);
        
        if (preg_match('/shopee/i', $order->original_filename)) {
            $platformId = 2;
            $platformName = 'Shopee';
        } elseif (preg_match('/lazada/i', $order->original_filename)) {
            $platformId = 1;
            $platformName = 'Lazada';
        } elseif (preg_match('/tiktok|tik tok/i', $order->original_filename)) {
            $platformId = 3;
            $platformName = 'TikTok';
        } elseif (preg_match('/tokopedia|tokped/i', $order->original_filename)) {
            $platformId = 4;
            $platformName = 'Tokopedia';
        } elseif (preg_match('/bukalapak/i', $order->original_filename)) {
            $platformId = 5;
            $platformName = 'Bukalapak';
        } elseif (preg_match('/blibli/i', $order->original_filename)) {
            $platformId = 6;
            $platformName = 'Blibli';
        }
    }
    
    if ($platformId && $platformId != $order->three_pl_id) {
        DB::table('order_label')
            ->where('id', $order->id)
            ->update(['three_pl_id' => $platformId]);
        
        echo "✓ Order #{$order->id} ({$order->code}) updated to {$platformName}\n";
        $updated++;
    } else {
        echo "- Order #{$order->id} ({$order->code}): " . ($platformName ?? 'Unknown') . " (no change)\n";
    }
}

echo "\n=== Summary ===\n";
echo "Total orders processed: {$orders->count()}\n";
echo "Updated: {$updated}\n";

echo "\n=== Platform Distribution ===\n";
$distribution = DB::table('order_label')
    ->leftJoin('three_pl', 'order_label.three_pl_id', '=', 'three_pl.id')
    ->select(DB::raw('COALESCE(three_pl.name, \'Unknown\') as platform'), DB::raw('count(*) as count'))
    ->where('order_label.saved', 1)
    ->groupBy(DB::raw('COALESCE(three_pl.name, \'Unknown\')'))
    ->orderBy('count', 'desc')
    ->get();

foreach ($distribution as $dist) {
    echo "{$dist->platform}: {$dist->count} orders\n";
}
