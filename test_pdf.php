<?php
require __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser;

echo "=== PDF Analysis ===\n";

$pdfPath = __DIR__ . '/storage/app/public/order-label-splits/ilovepdf_merged_original.pdf';

if (!file_exists($pdfPath)) {
    echo "PDF file not found at: " . $pdfPath . "\n";
    exit(1);
}

echo "File exists: " . $pdfPath . "\n";
echo "File size: " . filesize($pdfPath) . " bytes\n";

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    $pages = $pdf->getPages();

    echo "Total pages: " . count($pages) . "\n";

    if (count($pages) > 0) {
        $firstPageText = $pages[0]->getText();
        echo "First page text sample (first 300 chars):\n";
        echo substr($firstPageText, 0, 300) . "...\n";
    }

} catch (Exception $e) {
    echo "Error reading PDF: " . $e->getMessage() . "\n";

    // Try alternative approach with basic file info
    $handle = fopen($pdfPath, 'rb');
    $header = fread($handle, 100);
    fclose($handle);
    echo "PDF Header: " . bin2hex(substr($header, 0, 20)) . "\n";
}
