<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

use App\Models\OrderLabel;
use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;

echo "=== Testing PDF Split for Tes error.pdf ===\n";

$pdfPath = storage_path('app/public/order-label-splits/tes_error.pdf');
$outputDir = storage_path('app/public/order-label-splits/');
$originalName = 'tes_error.pdf';
$baseName = pathinfo($originalName, PATHINFO_FILENAME);

// Parse the PDF first
try {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    $pages = $pdf->getPages();
    $pageCount = count($pages);

    echo "Found {$pageCount} pages in PDF\n";

    for ($i = 1; $i <= $pageCount; $i++) {
        try {
            echo "Processing page {$i}...\n";

            // Try FPDI approach for individual file
            $fpdi = new Fpdi();
            $fpdi->setSourceFile($pdfPath);
            $fpdi->AddPage();
            $tplId = $fpdi->importPage($i);
            $fpdi->useTemplate($tplId);

            $splitFileName = $baseName . '_page_' . $i . '.pdf';
            $splitFilePath = $outputDir . $splitFileName;

            $fpdi->Output('F', $splitFilePath);

            echo "Split file created: {$splitFilePath}\n";

            // Extract text from this page
            $pageText = isset($pages[$i - 1]) ? $pages[$i - 1]->getText() : '';
            echo "Extracted text length: " . strlen($pageText) . "\n";

            // Save to database
            $orderLabel = OrderLabel::create([
                'code' => $baseName . '_' . $i . '_' . time(),
                'order_date' => now(),
                'note' => 'Test PDF Split: ' . $splitFileName,
                'original_filename' => $originalName,
                'split_filename' => $splitFileName,
                'page_number' => $i,
                'file_path' => 'order-label-splits/' . $splitFileName,
                'extracted_text' => $pageText,
                'status' => 'open',
                'saved' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            echo "Database record created with ID: {$orderLabel->id}\n";

        } catch (Exception $e) {
            echo "Error processing page {$i}: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error parsing PDF: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Done!\n";

?>
