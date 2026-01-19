<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Analyzing Order Label Platform Data ===\n\n";

$orders = DB::table('order_label')
    ->select('id', 'code', 'split_filename', 'three_pl_id', 'extracted_text', 'original_filename')
    ->where('saved', 1)
    ->get();

echo "Total orders: {$orders->count()}\n\n";

foreach ($orders as $order) {
    echo "Order ID: {$order->id}\n";
    echo "  Code: {$order->code}\n";
    echo "  Filename: {$order->split_filename}\n";
    echo "  Original: {$order->original_filename}\n";
    echo "  Current 3PL ID: {$order->three_pl_id}\n";

    // Detect platform from filename or code
    $detectedPlatform = null;
    $platformId = null;

    $text = strtolower($order->split_filename . ' ' . $order->code . ' ' . $order->extracted_text);

    if (preg_match('/shopee|spe/i', $text)) {
        $detectedPlatform = 'Shopee';
        $platformId = 2;
    } elseif (preg_match('/tiktok|tkt|tt/i', $text) || preg_match('/^[A-Z]{2}\d{12,}$/', $order->code)) {
        $detectedPlatform = 'TikTok';
        $platformId = 3;
    } elseif (preg_match('/lazada|lzd/i', $text)) {
        $detectedPlatform = 'Lazada';
        $platformId = 1;
    } elseif (preg_match('/tokopedia|tpd/i', $text)) {
        $detectedPlatform = 'Tokopedia';
        $platformId = 4;
    } elseif (preg_match('/bukalapak|bkl/i', $text)) {
        $detectedPlatform = 'Bukalapak';
        $platformId = 5;
    } elseif (preg_match('/blibli|blb/i', $text)) {
        $detectedPlatform = 'Blibli';
        $platformId = 6;
    }

    echo "  Detected: " . ($detectedPlatform ?? 'Unknown') . " (ID: " . ($platformId ?? 'NULL') . ")\n";

    if ($platformId && $platformId != $order->three_pl_id) {
        echo "  ⚠️ MISMATCH! Should update to {$detectedPlatform}\n";
    }

    echo "\n";
}

echo "\n=== Recommendation ===\n";
echo "Run sync_platform_data.php to update three_pl_id based on detection\n";
