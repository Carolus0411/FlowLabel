<?php

require __DIR__.'/vendor/autoload.php';

use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;

echo "=== Testing FPDI Split for ilovepdf_merge1d_copy.pdf ===\n";

$pdfPath = __DIR__ . '/storage/app/public/order-label-splits/ilovepdf_merge1d_copy.pdf';
$outputDir = __DIR__ . '/storage/app/public/order-label-splits/';
$baseName = 'ilovepdf_merge1d_copy';

try {
    // Parse the PDF first
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    $pages = $pdf->getPages();
    $pageCount = count($pages);

    echo "Found {$pageCount} pages in PDF\n";

    // Test first page
    $i = 1;
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

    // Test extract Order ID from text
    $pageText = isset($pages[$i - 1]) ? $pages[$i - 1]->getText() : '';
    echo "Extracted text length: " . strlen($pageText) . "\n";

    // Test extractOrderId logic
    if (preg_match('/(?:TT\s*)?Order\s*Id\s*[:\s：\.]*\s*(\d{15,})/iu', $pageText, $matches)) {
        echo "Order ID found: " . $matches[1] . "\n";
    } else {
        echo "Order ID not found in text\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Done!\n";

?>
