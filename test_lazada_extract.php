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

    foreach ($pages as $index => $page) {
        $pageNum = $index + 1;
        echo "=== PAGE $pageNum ===\n";

        // Try to extract all text nodes
        try {
            $text = $page->getText();
            echo "Extracted text: '$text'\n";
            echo "Length: " . strlen($text) . "\n";
        } catch (\Exception $e) {
            echo "Error extracting text: " . $e->getMessage() . "\n";
        }

        // Get all data streams
        $dataStreams = $page->getDataTm();
        if (!empty($dataStreams)) {
            echo "\nData streams found:\n";
            foreach ($dataStreams as $stream) {
                print_r($stream);
            }
        }

        // Try raw content
        echo "\nRaw content (first 2000 chars):\n";
        $content = $page->getContent();
        echo substr($content, 0, 2000) . "\n";

        // Look for text patterns in raw content
        if (preg_match_all('/\(([^)]+)\)/m', $content, $matches)) {
            echo "\nText strings found in parentheses:\n";
            foreach ($matches[1] as $str) {
                if (strlen($str) > 2) {
                    echo "  - " . $str . "\n";
                    // Check if it contains our order ID
                    if (strpos($str, '2758420016283453') !== false) {
                        echo "    *** FOUND ORDER ID! ***\n";
                    }
                }
            }
        }

        // Look for hex strings
        if (preg_match_all('/<([0-9a-fA-F]+)>/m', $content, $hexMatches)) {
            echo "\nHex strings found:\n";
            foreach ($hexMatches[1] as $hex) {
                if (strlen($hex) > 10) {
                    echo "  Hex: $hex\n";
                    // Try to decode
                    $decoded = hex2bin($hex);
                    if ($decoded) {
                        echo "  Decoded: $decoded\n";
                        if (strpos($decoded, '2758420016283453') !== false) {
                            echo "    *** FOUND ORDER ID in hex! ***\n";
                        }
                    }
                }
            }
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
