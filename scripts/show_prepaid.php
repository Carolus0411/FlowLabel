<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== All Prepaid Account entries ===\n";
$rows = DB::table('prepaid_account')
    ->select('id','code','date','coa_code','source_type','source_code','supplier_id','contact_id','debit','credit','note')
    ->orderBy('id','asc')
    ->get();

echo str_repeat('-', 150) . "\n";
foreach ($rows as $row) {
    $party = $row->contact_id ? "contact={$row->contact_id}" : ($row->supplier_id ? "supplier={$row->supplier_id}" : "none");
    echo sprintf("id=%d | code=%s | coa=%s | source=%s | ref=%s | %s | debit=%.2f | credit=%.2f\n",
        $row->id,
        $row->code,
        $row->coa_code,
        str_replace('App\\Models\\', '', $row->source_type ?? ''),
        $row->source_code ?? '',
        $party,
        $row->debit,
        $row->credit
    );
}

echo "\n=== Summary by party ===\n";
$summary = DB::table('prepaid_account')
    ->selectRaw("COALESCE(contact_id, 0) as contact_id, COALESCE(supplier_id, 0) as supplier_id, SUM(debit) as total_debit, SUM(credit) as total_credit")
    ->groupBy('contact_id', 'supplier_id')
    ->get();
foreach ($summary as $s) {
    $party = $s->contact_id ? "contact={$s->contact_id}" : ($s->supplier_id ? "supplier={$s->supplier_id}" : "none");
    echo sprintf("%s: debit=%.2f, credit=%.2f, balance=%.2f\n", $party, $s->total_debit, $s->total_credit, $s->total_credit - $s->total_debit);
}
