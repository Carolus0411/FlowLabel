<?php

require __DIR__.'/vendor/autoload.php';

use Smalot\PdfParser\Parser;

$pdfPath = 'D:\\Lazada.pdf';

if (!file_exists($pdfPath)) {
    echo "PDF file not found: $pdfPath\n";
    exit;
}

echo "Reading PDF: $pdfPath\n\n";

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);

    $pages = $pdf->getPages();
    $pageCount = count($pages);

    echo "Total pages: $pageCount\n\n";

    foreach ($pages as $index => $page) {
        $pageNum = $index + 1;
        echo "=== PAGE $pageNum ===\n";
        $text = $page->getText();
        echo $text . "\n";
        echo "\n--- Looking for Order ID patterns ---\n";

        // Try different patterns
        if (preg_match('/Order\s*(?:No|ID)\s*[:\.]?\s*(\d{16})/i', $text, $matches)) {
            echo "Pattern 1 (Order No/ID + 16 digits): " . $matches[1] . "\n";
        }

        if (preg_match('/\b(\d{16})\b/', $text, $matches)) {
            echo "Pattern 2 (Standalone 16 digits): " . $matches[1] . "\n";
        }

        if (preg_match('/Order\s*[:\.]?\s*(\d{14,17})/i', $text, $matches)) {
            echo "Pattern 3 (Order + 14-17 digits): " . $matches[1] . "\n";
        }

        // Try to find any sequence of 16 digits
        if (preg_match_all('/\d{16}/', $text, $allMatches)) {
            echo "All 16-digit sequences found: " . implode(', ', $allMatches[0]) . "\n";
        }

        echo "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
