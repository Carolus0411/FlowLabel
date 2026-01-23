<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\DB;

// Path ke PDF
$pdfPath = 'd:\Tes error.pdf';

if (!file_exists($pdfPath)) {
    echo "PDF file not found: $pdfPath\n";
    exit;
}

echo "Processing PDF with OCR: $pdfPath\n\n";

try {
    // Konversi PDF ke gambar menggunakan Ghostscript
    $imagePath = tempnam(sys_get_temp_dir(), 'pdf_page_') . '.png';
    $gsCommand = "gswin64c -dNOPAUSE -dBATCH -sDEVICE=png16m -r300 -sOutputFile=\"$imagePath\" \"$pdfPath\"";
    exec($gsCommand, $output, $returnVar);

    if ($returnVar !== 0) {
        echo "Error converting PDF to image: " . implode("\n", $output) . "\n";
        exit;
    }

    echo "Converted PDF to image: $imagePath\n";

    // OCR menggunakan Tesseract
    $tesseract = new TesseractOCR($imagePath);
    $text = $tesseract->run();

    echo "Extracted text length: " . strlen($text) . "\n";
    echo "First 500 chars:\n" . substr($text, 0, 500) . "\n";

    // Hapus file gambar sementara
    unlink($imagePath);

    // Insert ke database
    DB::table('pdf_contents')->insert([
        'filename' => 'Tes error.pdf',
        'content' => $text,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    echo "Data inserted to database successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>