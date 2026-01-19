<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

use App\Models\OrderLabel;
use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;

echo "=== Processing 599-page PDF ===\n";

$pdfPath = storage_path('app/public/order-label-splits/ilovepdf_merged_original.pdf');
$outputDir = storage_path('app/public/order-label-splits/');
$originalName = 'ilovepdf_merged_original.pdf';
$baseName = pathinfo($originalName, PATHINFO_FILENAME);

// Parse the PDF first
$parser = new Parser();
$pdf = $parser->parseFile($pdfPath);
$pages = $pdf->getPages();
$pageCount = count($pages);

echo "Found {$pageCount} pages in PDF\n";

// Process pages in batches to avoid memory issues
$batchSize = 50;
$totalBatches = ceil($pageCount / $batchSize);
$processed = 0;
$errors = 0;

for ($batch = 0; $batch < $totalBatches; $batch++) {
    $startPage = $batch * $batchSize + 1;
    $endPage = min(($batch + 1) * $batchSize, $pageCount);

    echo "Processing batch " . ($batch + 1) . "/{$totalBatches}: pages {$startPage}-{$endPage}\n";

    for ($i = $startPage; $i <= $endPage; $i++) {
        try {
            // Try FPDI approach for individual file
            $fpdi = new Fpdi();
            $fpdi->setSourceFile($pdfPath);
            $fpdi->AddPage();
            $tplId = $fpdi->importPage($i);
            $fpdi->useTemplate($tplId);

            $splitFileName = $baseName . '_page_' . $i . '.pdf';
            $splitFilePath = $outputDir . $splitFileName;

            $fpdi->Output('F', $splitFilePath);

            // Extract text from this page
            $pageText = isset($pages[$i - 1]) ? $pages[$i - 1]->getText() : '';

            // Save to database
            OrderLabel::create([
                'code' => $baseName . '_' . $i . '_' . time(),
                'order_date' => now(),
                'note' => 'PDF Split: ' . $splitFileName,
                'original_filename' => $originalName,
                'split_filename' => $splitFileName,
                'page_number' => $i,
                'file_path' => 'order-label-splits/' . $splitFileName,
                'extracted_text' => $pageText,
                'status' => 'open',
                'saved' => 1,
                'created_by' => 1, // Assuming admin user ID is 1
                'updated_by' => 1,
            ]);

            $processed++;

        } catch (Exception $e) {
            echo "Error processing page {$i}: " . $e->getMessage() . "\n";

            // Create fallback record for this page
            OrderLabel::create([
                'code' => $baseName . '_' . $i . '_fallback_' . time(),
                'order_date' => now(),
                'note' => 'PDF Page Split Failed: ' . $e->getMessage(),
                'original_filename' => $originalName,
                'split_filename' => null,
                'page_number' => $i,
                'file_path' => 'order-label-splits/' . $originalName,
                'extracted_text' => isset($pages[$i - 1]) ? $pages[$i - 1]->getText() : '',
                'status' => 'open',
                'saved' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $errors++;
        }

        if ($i % 10 == 0) {
            echo "  Processed page {$i}/{$pageCount}\n";
        }
    }

    // Clear memory
    gc_collect_cycles();
}

echo "\n=== Processing Complete ===\n";
echo "Total pages processed: {$processed}\n";
echo "Errors encountered: {$errors}\n";
echo "Database records created: " . OrderLabel::count() . "\n";

// Clean up original file if all pages were split successfully
if ($errors == 0 && $processed == $pageCount) {
    echo "All pages split successfully. Original file kept for reference.\n";
}

echo "Done!\n";
