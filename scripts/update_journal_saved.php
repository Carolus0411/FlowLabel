<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;


$code = 'JV/25/12/0017';

if (! DB::table('journal')->where('code', $code)->exists()) {
    echo "Journal not found: $code\n";
    exit(1);
}

$dt = DB::table('journal_detail')->where('code', $code)->sum('debit');
$ct = DB::table('journal_detail')->where('code', $code)->sum('credit');

DB::table('journal')->where('code', $code)->update(['saved' => 1, 'debit_total' => $dt, 'credit_total' => $ct]);

echo "Updated $code: saved=1, debit_total=$dt, credit_total=$ct\n";
