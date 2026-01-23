<?php

require __DIR__.'/vendor/autoload.php';

use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;

$pdfPath = __DIR__ . '/storage/app/public/order-label-splits/ilovepdf_merge1d_original.pdf';

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

    // Analyze first few pages
    $pagesToCheck = min(5, $pageCount);
    for ($i = 0; $i < $pagesToCheck; $i++) {
        $page = $pages[$i];
        $pageNum = $i + 1;
        echo "=== PAGE $pageNum ===\n";
        $text = $page->getText();
        echo "Text length: " . strlen($text) . "\n";
        echo "First 300 chars:\n" . substr($text, 0, 300) . "\n";

        // Check for Order ID patterns
        // Test improved patterns
        $preprocessedText = preg_replace('/\s+/', ' ', $text); // Simple preprocess

        if (preg_match('/(?:TT\s*)?Order\s*Id\s*[:\s：\.]*\s*(\d{15,})/iu', $preprocessedText, $matches)) {
            echo "✓ Order ID found (standard): " . $matches[1] . "\n";
        } elseif (preg_match('/Ship\s*:\s*Order\s*Id\s*[:\s：\.]*\s*(\d{15,})/iu', $preprocessedText, $matches)) {
            echo "✓ Order ID found (ship pattern): " . $matches[1] . "\n";
        } elseif (preg_match('/\b(\d{15,})\b/', $preprocessedText, $matches)) {
            echo "✓ Order ID found (fallback): " . $matches[1] . "\n";
        } else {
            echo "✗ Order ID not found\n";
        }

        echo "\n";
    }

    // Test FPDI split on first page
    echo "=== Testing FPDI Split ===\n";

    $fpdi = new Fpdi();
    try {
        $fpdi->setSourceFile($pdfPath);
        $tplId = $fpdi->importPage(1);
        $size = $fpdi->getTemplateSize($tplId);

        $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $fpdi->useTemplate($tplId);

        $testFile = __DIR__ . '/storage/app/public/order-label-splits/test_split_original.pdf';
        $fpdi->Output('F', $testFile);

        echo "✓ FPDI split successful, file size: " . filesize($testFile) . " bytes\n";
    } catch (Exception $e) {
        echo "✗ FPDI split failed: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Error reading PDF: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>
