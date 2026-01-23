<?php

require __DIR__.'/vendor/autoload.php';

use Smalot\PdfParser\Parser;

$pdfPath = __DIR__ . '/storage/app/public/order-label-splits/ilovepdf_merge1d_copy.pdf';

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
        echo "Text length: " . strlen($text) . "\n";
        echo "First 500 chars:\n" . substr($text, 0, 500) . "\n";

        // Try to extract Order ID patterns
        echo "\n--- Looking for Order ID patterns ---\n";

        // Lazada patterns
        if (preg_match('/Order\s*(?:No|ID)\s*[:\.]?\s*(\d{16})/i', $text, $matches)) {
            echo "Lazada Pattern 1 (Order No/ID + 16 digits): " . $matches[1] . "\n";
        }

        if (preg_match('/\b(\d{16})\b/', $text, $matches)) {
            echo "Lazada Pattern 2 (Standalone 16 digits): " . $matches[1] . "\n";
        }

        // TikTok patterns
        if (preg_match('/(?:TT\s*)?Order\s*Id\s*[:\s：\.]*\s*(\d{15,})/iu', $text, $matches)) {
            echo "TikTok Pattern (Order Id + digits): " . $matches[1] . "\n";
        }

        // Shopee patterns
        if (preg_match('/Order\s*ID\s*[:\.]?\s*(\d{10,})/i', $text, $matches)) {
            echo "Shopee Pattern (Order ID + digits): " . $matches[1] . "\n";
        }

        echo "\n";
    }

} catch (Exception $e) {
    echo "Error reading PDF: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>
