<?php
require __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser;

$pdfPath = __DIR__ . '/kenapa error.pdf';

if (!file_exists($pdfPath)) {
    echo "PDF file not found: $pdfPath\n";
    exit;
}

echo "Analyzing PDF: $pdfPath\n\n";

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    $pages = $pdf->getPages();
    $pageCount = count($pages);

    echo "Total pages: $pageCount\n\n";
    $foundOrderId = false;
    for ($i = 0; $i < $pageCount; $i++) {
        $page = $pages[$i];
        $pageNum = $i + 1;
        $text = $page->getText();
        echo "=== PAGE $pageNum ===\n";
        echo "Text length: " . strlen($text) . "\n";
        echo "First 300 chars:\n" . substr($text, 0, 300) . "\n";

        // Preprocess text
        $preprocessedText = preg_replace('/\s+/', ' ', $text);

        // Try to extract Order ID
        if (preg_match('/(?:TT\s*)?Order\s*Id\s*[:\s：\.]*\s*(\d{15,})/iu', $preprocessedText, $matches)) {
            echo "✓ Order ID found (standard): " . $matches[1] . "\n";
            $foundOrderId = true;
        } elseif (preg_match('/Ship\s*:\s*Order\s*Id\s*[:\s：\.]*\s*(\d{15,})/iu', $preprocessedText, $matches)) {
            echo "✓ Order ID found (ship pattern): " . $matches[1] . "\n";
            $foundOrderId = true;
        } elseif (preg_match('/\b(\d{15,})\b/', $preprocessedText, $matches)) {
            echo "✓ Order ID found (fallback): " . $matches[1] . "\n";
            $foundOrderId = true;
        } else {
            echo "✗ Order ID not found\n";
        }
        echo "\n";
    }
    if (!$foundOrderId) {
        echo "No valid Order ID found in any page.\n";
    }
} catch (Exception $e) {
    echo "Error reading PDF: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>