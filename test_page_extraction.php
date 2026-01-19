<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrderLabel;
use Illuminate\Support\Facades\Storage;

// Test the page extraction functionality
echo "Testing page extraction functionality...\n\n";

// Get the first few OrderLabel records
$labels = OrderLabel::take(3)->get();

echo "Testing with first 3 OrderLabel records:\n";
foreach ($labels as $label) {
    echo "ID: {$label->id}, Page: {$label->page_number}, File: {$label->file_path}\n";
    
    // Check if the file exists
    if (Storage::disk('public')->exists($label->file_path)) {
        $fullPath = Storage::disk('public')->path($label->file_path);
        echo "  ✓ File exists at: {$fullPath}\n";
        
        // Test FPDI extraction
        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($fullPath);
            echo "  ✓ PDF has {$pageCount} pages\n";
            
            if ($label->page_number <= $pageCount) {
                echo "  ✓ Can import page {$label->page_number}\n";
                
                // Try to extract the page
                $tplIdx = $pdf->importPage($label->page_number);
                $pdf->AddPage();
                $pdf->useTemplate($tplIdx);
                
                // Test output to temporary location
                $tempPath = storage_path('app/temp/test_page_' . $label->id . '.pdf');
                if (!is_dir(dirname($tempPath))) {
                    mkdir(dirname($tempPath), 0755, true);
                }
                
                $pdf->Output($tempPath, 'F');
                
                if (file_exists($tempPath)) {
                    $size = filesize($tempPath);
                    echo "  ✓ Successfully extracted page to temp file ({$size} bytes)\n";
                    unlink($tempPath); // Clean up
                } else {
                    echo "  ✗ Failed to create temp file\n";
                }
                
            } else {
                echo "  ✗ Page {$label->page_number} exceeds PDF page count ({$pageCount})\n";
            }
            
        } catch (Exception $e) {
            echo "  ✗ FPDI extraction failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "  ✗ File does not exist\n";
    }
    echo "\n";
}

echo "Test completed.\n";