<?php

require __DIR__.'/vendor/autoload.php';

use Smalot\PdfParser\Parser;

$pdfPath = __DIR__ . '/storage/app/public/order-label-splits/tes_error.pdf';

if (!file_exists($pdfPath)) {
    echo "PDF file not found: $pdfPath\n";
    exit;
}

echo "Re-checking PDF: $pdfPath\n\n";

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);

    $pages = $pdf->getPages();
    $pageCount = count($pages);

    echo "Total pages: $pageCount\n\n";

    foreach ($pages as $index => $page) {
        $pageNum = $index + 1;
        echo "=== PAGE $pageNum ===\n";

        // Try different text extraction methods
        $textNormalized = $page->getText(); // default
        $textRaw = $page->getText(); // same as normalized

        echo "Text length: " . strlen($textNormalized) . "\n";
        echo "Text first 500 chars:\n" . substr($textNormalized, 0, 500) . "\n";

        // Try to get page details
        // Note: getData() not available, using available methods

        // Look for the specific Order ID mentioned
        $orderId = '582216242924061869';
        if (strpos($textNormalized, $orderId) !== false) {
            echo "FOUND Order ID $orderId in text!\n";
        } else {
            echo "Order ID $orderId NOT found in text\n";
        }

        // Try regex patterns
        echo "\n--- Looking for Order ID patterns ---\n";

        // General long number pattern
        if (preg_match('/\b(\d{15,})\b/', $textNormalized, $matches)) {
            echo "Found long number: " . $matches[1] . "\n";
        }

        // Specific pattern
        if (preg_match('/Order\s*Id\s*[:\s：\.]*\s*(\d{15,})/iu', $textNormalized, $matches)) {
            echo "Order Id pattern found: " . $matches[1] . "\n";
        }

        echo "\n";
    }

} catch (Exception $e) {
    echo "Error reading PDF: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>
