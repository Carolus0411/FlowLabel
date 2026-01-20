<?php

require __DIR__.'/vendor/autoload.php';

use Smalot\PdfParser\Parser;

$pdfPath = 'd:\\test\\ilovepdf_merge1d - Copy.pdf';

echo "Checking PDF: $pdfPath\n";

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    $pages = $pdf->getPages();
    $pageCount = count($pages);

    echo "Total pages detected: $pageCount\n\n";

    // Check first 5 pages for order ID
    echo "Checking first 5 pages for order ID extraction:\n";
    for ($i = 0; $i < min(5, $pageCount); $i++) {
        $pageNumber = $i + 1;
        echo "\n--- Page $pageNumber ---\n";
        try {
            $text = $pages[$i]->getText();
            echo "Text length: " . strlen($text) . " chars\n";

            // Extract order ID using TikTok pattern
            if (preg_match('/(?:TT\s*)?Order\s*Id\s*[:\.]?\s*(\d{15,})/i', $text, $matches)) {
                echo "Order ID found: " . $matches[1] . "\n";
            } elseif (preg_match('/\b(\d{15,})\b/', $text, $matches)) {
                echo "Long number found: " . $matches[1] . "\n";
            } else {
                echo "No order ID found\n";
                echo "First 200 chars: " . substr($text, 0, 200) . "\n";
            }
        } catch (Exception $e) {
            echo "Error extracting text: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
