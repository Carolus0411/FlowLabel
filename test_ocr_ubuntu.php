<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Tesseract OCR Test ===\n\n";

// Test 1: Check if TesseractOCR class can be loaded
echo "1. Testing TesseractOCR class loading...\n";
try {
    $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR();
    echo "✓ TesseractOCR class loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Failed to load TesseractOCR class: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check configuration
echo "\n2. Testing OCR configuration...\n";
$configPath = config('ocr.tesseract_path', 'tesseract');
$lang = config('ocr.lang', 'eng');
echo "Tesseract path: $configPath\n";
echo "Default language: $lang\n";

// Test 3: Check if tesseract executable exists
echo "\n3. Testing Tesseract executable...\n";
$executable = config('ocr.tesseract_path', 'tesseract');
exec("$executable --version 2>&1", $output, $returnCode);
if ($returnCode === 0) {
    echo "✓ Tesseract executable found\n";
    echo "Version info: " . implode("\n", array_slice($output, 0, 2)) . "\n";
} else {
    echo "✗ Tesseract executable not found or not working\n";
    echo "Command: $executable --version\n";
    echo "Return code: $returnCode\n";
    if (!empty($output)) {
        echo "Output: " . implode("\n", $output) . "\n";
    }
}

// Test 4: Check available languages
echo "\n4. Testing available languages...\n";
exec("$executable --list-langs 2>&1", $langOutput, $langReturnCode);
if ($langReturnCode === 0) {
    echo "✓ Available languages:\n";
    foreach ($langOutput as $line) {
        if (!empty(trim($line)) && !str_contains($line, 'List of available languages')) {
            echo "  - $line\n";
        }
    }
} else {
    echo "✗ Could not list languages\n";
}

// Test 5: Create a simple test image and try OCR
echo "\n5. Testing OCR functionality...\n";
$testImagePath = storage_path('app/temp/ocr_test.png');
$testDir = dirname($testImagePath);

if (!is_dir($testDir)) {
    mkdir($testDir, 0755, true);
}

// Create a simple test image with text using ImageMagick if available
$text = 'TEST OCR TEXT 123';
exec("convert -size 200x50 -pointsize 20 -fill black -background white -gravity center label:'$text' $testImagePath 2>&1", $convertOutput, $convertCode);

if ($convertCode === 0 && file_exists($testImagePath)) {
    echo "✓ Test image created\n";

    try {
        $ocrResult = (new \thiagoalessio\TesseractOCR\TesseractOCR($testImagePath))
            ->executable($executable)
            ->lang($lang)
            ->run();

        echo "✓ OCR test successful\n";
        echo "Extracted text: '" . trim($ocrResult) . "'\n";

        // Clean up
        @unlink($testImagePath);

    } catch (Exception $e) {
        echo "✗ OCR test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Could not create test image (ImageMagick may not be available)\n";
    if (!empty($convertOutput)) {
        echo "ImageMagick output: " . implode("\n", $convertOutput) . "\n";
    }
}

echo "\n=== Test Complete ===\n";
?></content>
<parameter name="filePath">d:\Laravel\labelsysflow\test_ocr_ubuntu.php