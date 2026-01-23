<?php

require __DIR__.'/vendor/autoload.php';

use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;

echo "=== Testing FPDI Split for Tes error.pdf ===\n";

$pdfPath = __DIR__ . '/storage/app/public/order-label-splits/tes_error.pdf';
$outputDir = __DIR__ . '/storage/app/public/order-label-splits/';
$baseName = 'tes_error';

try {
    // Parse the PDF first
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    $pages = $pdf->getPages();
    $pageCount = count($pages);

    echo "Found {$pageCount} pages in PDF\n";

    for ($i = 1; $i <= $pageCount; $i++) {
        try {
            echo "Processing page {$i}...\n";

            // Try FPDI approach
            $fpdi = new Fpdi();
            $fpdi->setSourceFile($pdfPath);

            $tplId = $fpdi->importPage($i);
            $size = $fpdi->getTemplateSize($tplId);

            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';

            $fpdi->AddPage($orientation, [$size['width'], $size['height']]);
            $fpdi->useTemplate($tplId);

            $splitFileName = $baseName . '_page_' . $i . '.pdf';
            $splitFilePath = $outputDir . $splitFileName;

            $fpdi->Output('F', $splitFilePath);

            echo "Split file created successfully: {$splitFilePath}\n";

            // Check if file exists and size
            if (file_exists($splitFilePath)) {
                echo "File size: " . filesize($splitFilePath) . " bytes\n";
            } else {
                echo "ERROR: Split file not created!\n";
            }

        } catch (Exception $e) {
            echo "ERROR processing page {$i}: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR parsing PDF: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Done!\n";

?>
