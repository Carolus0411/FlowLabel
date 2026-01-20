<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrderLabel;

// Get latest batch
$latestBatch = OrderLabel::orderBy('created_at', 'desc')->first();

if ($latestBatch) {
    $batchNo = $latestBatch->batch_no;
    echo "Latest batch: $batchNo\n";

    $records = OrderLabel::where('batch_no', $batchNo)->get();
    echo "Total records in batch: " . $records->count() . "\n\n";

    // Check for duplicate page numbers
    $pageNumbers = $records->pluck('page_number')->toArray();
    $duplicates = array_diff_assoc($pageNumbers, array_unique($pageNumbers));

    if (!empty($duplicates)) {
        echo "Found duplicate page numbers!\n";
        print_r(array_unique($duplicates));
    }

    // Check for missing page numbers
    $maxPage = max($pageNumbers);
    $minPage = min($pageNumbers);
    echo "Page range: $minPage - $maxPage\n";

    $missingPages = [];
    for ($i = $minPage; $i <= $maxPage; $i++) {
        if (!in_array($i, $pageNumbers)) {
            $missingPages[] = $i;
        }
    }

    if (!empty($missingPages)) {
        echo "\nMissing page numbers (" . count($missingPages) . " pages):\n";
        echo implode(', ', $missingPages) . "\n";
    } else {
        echo "\nNo missing page numbers detected.\n";
    }

    // Show sample records
    echo "\nFirst 5 records:\n";
    foreach ($records->take(5) as $record) {
        echo "Page {$record->page_number}: {$record->code} - {$record->split_filename}\n";
    }

    echo "\nLast 5 records:\n";
    foreach ($records->sortByDesc('page_number')->take(5) as $record) {
        echo "Page {$record->page_number}: {$record->code} - {$record->split_filename}\n";
    }

} else {
    echo "No records found\n";
}
