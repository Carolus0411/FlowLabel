<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Checking Permissions Table ===\n\n";

// Check if permissions table exists
if (Schema::hasTable('permissions')) {
    echo "✓ Table 'permissions' EXISTS\n\n";

    // Get columns
    $columns = Schema::getColumnListing('permissions');
    echo "Columns in permissions table:\n";
    foreach ($columns as $column) {
        echo "  - $column\n";
    }

    // Count records
    $count = DB::table('permissions')->count();
    echo "\nTotal records: $count\n";

} else {
    echo "✗ Table 'permissions' DOES NOT EXIST\n\n";

    // List all tables
    echo "Available tables:\n";
    $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
    foreach ($tables as $table) {
        echo "  - " . $table->tablename . "\n";
    }
}
