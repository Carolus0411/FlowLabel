<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Checking Cache Tables:\n";
echo "=====================\n\n";

if (Schema::hasTable('cache')) {
    echo "✅ Cache table exists\n";
    $count = \DB::table('cache')->count();
    echo "   Records: $count\n";
} else {
    echo "❌ Cache table does NOT exist\n";
}

if (Schema::hasTable('cache_locks')) {
    echo "✅ Cache locks table exists\n";
    $count = \DB::table('cache_locks')->count();
    echo "   Records: $count\n";
} else {
    echo "❌ Cache locks table does NOT exist\n";
}

echo "\nCache Store: " . config('cache.default') . "\n";
echo "Cache Driver: " . config('cache.stores.database.driver') . "\n";
