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

    // Try to get details
    $details = $pdf->getDetails();
    echo "PDF Details:\n";
    print_r($details);
    echo "\n";

    // Try full text
    echo "Full PDF Text:\n";
    $fullText = $pdf->getText();
    echo $fullText . "\n";
    echo "Text length: " . strlen($fullText) . "\n\n";

    // Try to get objects
    $objects = $pdf->getObjects();
    echo "Total objects: " . count($objects) . "\n";

    // Look for text in objects
    foreach ($objects as $objId => $obj) {
        $content = $obj->getContent();
        if (!empty($content) && strlen($content) > 10) {
            echo "\nObject $objId content (first 500 chars):\n";
            echo substr($content, 0, 500) . "\n";

            // Look for number patterns in raw content
            if (preg_match('/\d{16}/', $content, $matches)) {
                echo "*** Found 16-digit number: " . $matches[0] . " ***\n";
            }
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
