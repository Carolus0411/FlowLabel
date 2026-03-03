<?php

require __DIR__.'/vendor/autoload.php';

use Smalot\PdfParser\Parser;

$pdfPath = 'd:\\ilovepdf_merged.pdf';

if (!file_exists($pdfPath)) {
    echo "PDF not found: $pdfPath\n";
    exit(1);
}

echo "Reading PDF: $pdfPath\n\n";

$parser = new Parser();
$pdf = $parser->parseFile($pdfPath);
$pages = $pdf->getPages();
$total = count($pages);

echo "Total pages: $total\n\n";

// Shopee patterns
$patterns = [
    'No.Pesanan (strict)'  => '/No\.?\s*Pesanan\s*[:\.]?\s*([A-Z0-9]{12,16})/i',
    'No.Pesanan (relaxed)' => '/No\.?\s*Pesanan\s*[:\s\.\-]*([A-Z0-9]{10,20})/i',
    'Order ID alphanum'    => '/Order\s*ID\s*[:\.]?\s*([A-Z0-9]{12,16})/i',
    'Standalone YYMMDD'    => '/\b(\d{6}[A-Z0-9]{8,10})\b/',
];

for ($i = 0; $i < min(5, $total); $i++) {
    $pageNum = $i + 1;
    echo "=== PAGE $pageNum ===\n";

    try {
        $text = $pages[$i]->getText();
        echo "Text:\n$text\n";

        // Show hex of first 300 chars to detect encoding issues
        echo "\nHex of No.Pesanan area:\n";
        if (preg_match('/.{0,5}No.{0,30}Pesanan.{0,50}/is', $text, $m)) {
            echo bin2hex($m[0]) . "\n";
            echo "Readable: " . $m[0] . "\n";
        }

        echo "\nPattern results:\n";
        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                echo "  [$name] MATCH: '{$matches[1]}'\n";
            } else {
                echo "  [$name] NO MATCH\n";
            }
        }

        // Find any "No" + "Pesanan" occurrence
        if (preg_match_all('/No.{0,5}Pesanan.{0,30}/i', $text, $allMatches)) {
            echo "\n  All 'No.Pesanan' occurrences:\n";
            foreach ($allMatches[0] as $match) {
                echo "    >> " . json_encode($match) . "\n";
                echo "    >> hex: " . bin2hex($match) . "\n";
            }
        }

    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    echo "\n";
}
