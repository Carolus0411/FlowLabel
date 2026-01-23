<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

use App\Models\OrderLabel;

echo "Testing OrderLabel insert with empty text\n";

try {
    $text = ''; // Empty text like in Tes error.pdf

    $orderLabel = OrderLabel::create([
        'batch_no' => 'TEST-' . date('Y-m-d'),
        'three_pl_id' => null,
        'code' => 'test_empty_' . time(),
        'order_date' => now(),
        'note' => 'Test insert with empty text',
        'original_filename' => 'tes_error.pdf',
        'split_filename' => 'tes_error_page_1.pdf',
        'page_number' => 1,
        'file_path' => 'order-label-splits/tes_error_page_1.pdf',
        'extracted_text' => $text,
        'status' => 'open',
        'saved' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    echo "Insert successful! ID: " . $orderLabel->id . "\n";
    echo "Code: " . $orderLabel->code . "\n";
    echo "Extracted text: '" . $orderLabel->extracted_text . "'\n";

} catch (Exception $e) {
    echo "Insert failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>
