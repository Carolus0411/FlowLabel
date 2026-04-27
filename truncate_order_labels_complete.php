<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrderLabel;
use App\Models\OrderLabelDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "Truncating Order Label Transactions\n";
echo "==================================\n\n";

try {
    // Handle Postgres vs MySQL
    $driver = DB::getDriverName();
    
    if ($driver === 'pgsql') {
        echo "Detected PostgreSQL driver.\n";
        echo "Truncating order_label_detail and order_label with CASCADE...\n";
        DB::statement('TRUNCATE TABLE order_label_detail, order_label RESTART IDENTITY CASCADE;');
    } else {
        echo "Detected $driver driver.\n";
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        echo "Truncating order_label_detail...\n";
        OrderLabelDetail::truncate();
        echo "Truncating order_label...\n";
        OrderLabel::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    echo "✓ Database tables truncated.\n\n";

    echo "Cleaning up files in storage/app/public/order-label-splits/...\n";
    $path = storage_path('app/public/order-label-splits');
    if (File::exists($path)) {
        $directories = File::directories($path);
        $dirCount = 0;
        foreach ($directories as $directory) {
            File::deleteDirectory($directory);
            $dirCount++;
        }
        
        $files = File::files($path);
        $fileCount = 0;
        foreach ($files as $file) {
             File::delete($file);
             $fileCount++;
        }
        echo "✓ Deleted $dirCount directories and $fileCount files from: $path\n";
    }

    echo "Resetting AutoCode for FLOW batches...\n";
    $affected = \App\Models\AutoCode::where('prefix', 'like', 'FLOW/%')->update(['num' => 0]);
    echo "✓ Reset $affected AutoCode records.\n";

    echo "\n✅ All order label transactions and files have been truncated.\n";

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
