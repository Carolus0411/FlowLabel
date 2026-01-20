<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrderLabel;
use Spatie\SimpleExcel\SimpleExcelWriter;

echo "Testing Excel Export\n";
echo "===================\n\n";

// Get sample data
$orderLabels = OrderLabel::stored()
    ->with('threePl')
    ->take(5)
    ->get();

echo "Found " . $orderLabels->count() . " records\n\n";

$rows = [];
foreach ($orderLabels as $orderLabel) {
    $row = [
        'Batch No' => $orderLabel->batch_no ?? '',
        'Platform' => $orderLabel->threePl?->name ?? '',
        'Code' => $orderLabel->code,
        'Status' => ucfirst($orderLabel->status),
        'Order Date' => $orderLabel->order_date ? \Carbon\Carbon::parse($orderLabel->order_date)->format('d-m-Y') : '',
        'Page' => $orderLabel->page_number ?? '',
    ];
    $rows[] = $row;
    echo "Row: " . json_encode($row) . "\n";
}

// Test creating Excel file
$filename = storage_path('app/test-export.xlsx');
$writer = SimpleExcelWriter::create($filename);
$writer->addRows($rows);

if (file_exists($filename)) {
    $size = filesize($filename);
    echo "\n✅ Excel file created successfully!\n";
    echo "   Location: $filename\n";
    echo "   Size: " . round($size / 1024, 2) . " KB\n";
    
    // Clean up
    unlink($filename);
    echo "   Test file deleted.\n";
} else {
    echo "\n❌ Failed to create Excel file\n";
}
