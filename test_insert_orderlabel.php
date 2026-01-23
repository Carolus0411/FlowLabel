<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

use App\Models\OrderLabel;
use Smalot\PdfParser\Parser;

echo "=== Testing OrderLabel Insert ===\n";

try {
    $pdfPath = storage_path('app/public/order-label-splits/tes_error.pdf');

    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    $pages = $pdf->getPages();
    $pageText = isset($pages[0]) ? $pages[0]->getText() : '';

    echo "Extracted text length: " . strlen($pageText) . "\n";

    $orderLabel = OrderLabel::create([
        'batch_no' => 'TEST-001',
        'three_pl_id' => null,
        'code' => 'test_code_' . time(),
        'order_date' => now(),
        'note' => 'Test insert for error PDF',
        'original_filename' => 'tes_error.pdf',
        'split_filename' => 'tes_error_page_1.pdf',
        'page_number' => 1,
        'file_path' => 'order-label-splits/tes_error_page_1.pdf',
        'extracted_text' => $pageText,
        'status' => 'open',
        'saved' => 1,
        'created_by' => 1,
        'updated_by' => 1,
    ]);

    echo "Insert successful! ID: " . $orderLabel->id . "\n";

} catch (Exception $e) {
    echo "Insert failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>
