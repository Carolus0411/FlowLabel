<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrderLabel;

echo "Checking Missing Pages from Latest Import\n";
echo "==========================================\n\n";

// Get latest batch
$latestBatch = OrderLabel::orderBy('created_at', 'desc')->first();

if (!$latestBatch) {
    echo "No batch found in database\n";
    exit;
}

$batchNo = $latestBatch->batch_no;
echo "Latest Batch: $batchNo\n";
echo "Original File: {$latestBatch->original_filename}\n\n";

// Get all records from this batch
$records = OrderLabel::where('batch_no', $batchNo)->orderBy('page_number')->get();
echo "Total Records: " . $records->count() . "\n";

// Find missing pages
$pageNumbers = $records->pluck('page_number')->toArray();
$minPage = min($pageNumbers);
$maxPage = max($pageNumbers);

echo "Page Range: $minPage - $maxPage\n\n";

$missingPages = [];
for ($i = $minPage; $i <= $maxPage; $i++) {
    if (!in_array($i, $pageNumbers)) {
        $missingPages[] = $i;
    }
}

if (!empty($missingPages)) {
    echo "❌ MISSING PAGES (" . count($missingPages) . "):\n";
    echo implode(', ', $missingPages) . "\n\n";
    
    // Check if these pages should have been created with Ghostscript fallback
    echo "Expected pages (should have been recovered by Ghostscript):\n";
    foreach ($missingPages as $pageNum) {
        echo "  - Page $pageNum: NOT in database\n";
    }
} else {
    echo "✓ No missing pages!\n";
}

echo "\n";

// Check saved status
$notSaved = $records->where('saved', 0);
if ($notSaved->count() > 0) {
    echo "⚠ PAGES WITHOUT FILES (" . $notSaved->count() . "):\n";
    echo "These pages are in database but files couldn't be generated:\n";
    foreach ($notSaved as $record) {
        echo "  - Page {$record->page_number}: {$record->code}\n";
    }
    echo "\n";
}

// Show pages with (Recovered) note
$recovered = $records->filter(function($record) {
    return str_contains($record->note, 'Recovered');
});

if ($recovered->count() > 0) {
    echo "✓ RECOVERED PAGES (" . $recovered->count() . "):\n";
    echo "These pages were recovered using Ghostscript fallback:\n";
    foreach ($recovered->take(10) as $record) {
        echo "  - Page {$record->page_number}: {$record->code} (saved: {$record->saved})\n";
    }
    if ($recovered->count() > 10) {
        echo "  ... and " . ($recovered->count() - 10) . " more\n";
    }
} else {
    echo "ℹ No pages marked as 'Recovered'\n";
}

echo "\n==========================================\n";
