<?php

require __DIR__.'/vendor/autoload.php';

use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;

$pdfPath = 'd:\\test\\ilovepdf_merge1d - Copy.pdf';
$missingPages = [27, 67, 179, 253, 335, 416, 428, 516, 524];

echo "Testing missing pages from PDF...\n\n";

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    $pages = $pdf->getPages();
    
    foreach ($missingPages as $pageNum) {
        echo "--- Testing Page $pageNum ---\n";
        
        try {
            // Test text extraction
            $pageIndex = $pageNum - 1;
            if (isset($pages[$pageIndex])) {
                $text = $pages[$pageIndex]->getText();
                echo "Text extraction: OK (length: " . strlen($text) . ")\n";
                
                // Try to extract order ID
                if (preg_match('/(?:TT\s*)?Order\s*Id\s*[:\.]?\s*(\d{15,})/i', $text, $matches)) {
                    echo "Order ID: " . $matches[1] . "\n";
                } elseif (preg_match('/\b(\d{15,})\b/', $text, $matches)) {
                    echo "Order ID: " . $matches[1] . "\n";
                } else {
                    echo "Order ID: NOT FOUND\n";
                    echo "First 200 chars: " . substr($text, 0, 200) . "\n";
                }
            } else {
                echo "Page not found in parser!\n";
            }
            
            // Test FPDI
            $fpdi = new Fpdi();
            $fpdi->setSourceFile($pdfPath);
            $tplId = $fpdi->importPage($pageNum);
            $size = $fpdi->getTemplateSize($tplId);
            echo "FPDI import: OK (size: {$size['width']}x{$size['height']})\n";
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}
