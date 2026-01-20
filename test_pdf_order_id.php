<?php

require __DIR__.'/vendor/autoload.php';

use Smalot\PdfParser\Parser;

$pdfPath = 'd:\\test\\ilovepdf_merge1d - Copy.pdf';

echo "Testing TikTok Order ID Extraction from Real PDF\n";
echo "=================================================\n\n";

// Pages that were previously failing
$testPages = [27, 67, 179, 253, 335];

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    $pages = $pdf->getPages();

    // Updated regex pattern that handles full-width colon
    $pattern = '/(?:TT\s*)?Order\s*Id\s*[:\s：\.]*\s*(\d{15,})/iu';

    foreach ($testPages as $pageNum) {
        $pageIndex = $pageNum - 1;

        echo "Page $pageNum:\n";

        if (isset($pages[$pageIndex])) {
            $text = $pages[$pageIndex]->getText();

            // Show relevant part of text
            if (preg_match('/(.{0,30}Order\s*Id.{0,50})/iu', $text, $context)) {
                echo "Context: \"" . trim($context[0]) . "\"\n";
            }

            // Try to extract Order ID
            if (preg_match($pattern, $text, $matches)) {
                echo "✓ Order ID found: {$matches[1]}\n";
            } else {
                echo "✗ Order ID NOT found\n";
                echo "First 300 chars:\n";
                echo substr($text, 0, 300) . "...\n";
            }
        } else {
            echo "✗ Page not found in PDF\n";
        }

        echo "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nPattern used:\n";
echo "$pattern\n\n";

echo "Key features:\n";
echo "✓ Supports regular colon ':'\n";
echo "✓ Supports full-width colon '：' (Chinese/Japanese)\n";
echo "✓ Handles various spacing\n";
echo "✓ Case insensitive\n";
echo "✓ Unicode aware (flag 'u')\n";
