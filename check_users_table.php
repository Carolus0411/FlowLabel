<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Columns in users table:\n";
$columns = Schema::getColumnListing('users');
foreach ($columns as $column) {
    echo "  - $column\n";
}
