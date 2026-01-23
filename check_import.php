<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$records = DB::table('order_label')->where('original_filename', 'Tes error.pdf')->get();

echo 'Total records: ' . $records->count() . "\n";

foreach($records as $r) {
    echo 'ID: ' . $r->id . ', Code: ' . $r->code . ', Order ID: ' . ($r->order_id ?? 'NULL') . ', Page: ' . $r->page_number . "\n";
}

?>