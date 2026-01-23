<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$data = DB::table('pdf_contents')->first();

if ($data) {
    echo "Filename: " . $data->filename . "\n";
    echo "Content length: " . strlen($data->content) . "\n";
    echo "First 200 chars: " . substr($data->content, 0, 200) . "\n";
} else {
    echo "No data found.\n";
}

?>