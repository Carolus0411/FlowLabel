<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CASH IN ===\n";
$cashIns = DB::table('cash_in')
    ->select('code', 'has_receivable', 'used_receivable', 'status')
    ->where('status', 'close')
    ->take(10)
    ->get();
foreach ($cashIns as $c) {
    echo "{$c->code} | has: {$c->has_receivable} | used: {$c->used_receivable} | status: {$c->status}\n";
}

echo "\n=== BANK IN ===\n";
$bankIns = DB::table('bank_in')
    ->select('code', 'has_receivable', 'used_receivable', 'status')
    ->where('status', 'close')
    ->take(10)
    ->get();
foreach ($bankIns as $b) {
    echo "{$b->code} | has: {$b->has_receivable} | used: {$b->used_receivable} | status: {$b->status}\n";
}

echo "\n=== CASH OUT ===\n";
$cashOuts = DB::table('cash_out')
    ->select('code', 'has_payable', 'used_payable', 'status')
    ->where('status', 'close')
    ->take(10)
    ->get();
foreach ($cashOuts as $c) {
    echo "{$c->code} | has: {$c->has_payable} | used: {$c->used_payable} | status: {$c->status}\n";
}

echo "\n=== BANK OUT ===\n";
$bankOuts = DB::table('bank_out')
    ->select('code', 'has_payable', 'used_payable', 'status')
    ->where('status', 'close')
    ->take(10)
    ->get();
foreach ($bankOuts as $b) {
    echo "{$b->code} | has: {$b->has_payable} | used: {$b->used_payable} | status: {$b->status}\n";
}

echo "\n=== SALES SETTLEMENT SOURCES ===\n";
$salesSources = DB::table('sales_settlement_source')
    ->select('id', 'sales_settlement_code', 'settleable_type', 'settleable_id')
    ->take(10)
    ->get();
foreach ($salesSources as $s) {
    $type = class_basename($s->settleable_type);
    echo "{$s->id} | {$s->sales_settlement_code} | {$type} | {$s->settleable_id}\n";
}

echo "\n=== PURCHASE SETTLEMENT SOURCES ===\n";
$purchaseSources = DB::table('purchase_settlement_source')
    ->select('id', 'purchase_settlement_code', 'settleable_type', 'settleable_id')
    ->take(10)
    ->get();
foreach ($purchaseSources as $s) {
    $type = class_basename($s->settleable_type);
    echo "{$s->id} | {$s->purchase_settlement_code} | {$type} | {$s->settleable_id}\n";
}
