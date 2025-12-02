<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$code = 'JV/25/12/0017';
$r = DB::table('journal')->where('code', $code)->first();
if (!$r) {
    echo "Not found\n";
    exit(1);
}

echo json_encode($r) . PHP_EOL;
