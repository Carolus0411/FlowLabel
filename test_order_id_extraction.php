<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test the extraction logic

// Test filenames
$testFilenames = [
    '2758420016283453.pdf',
    'Lazada_2758420016283453.pdf',
    'Label_2758420016283453_test.pdf',
    '2758420016283453',
    'Lazada.pdf', // Should fail
];

echo "=== Testing Order ID Extraction from Filenames ===\n\n";

foreach ($testFilenames as $filename) {
    echo "Filename: $filename\n";

    $baseName = pathinfo($filename, PATHINFO_FILENAME);

    // Lazada pattern: 16 digits
    if (preg_match('/(\d{16})/', $baseName, $matches)) {
        echo "  ✓ Found Order ID: " . $matches[1] . "\n";
    } else {
        echo "  ✗ No Order ID found\n";
    }

    echo "\n";
}

// Now test with actual job logic
echo "\n=== Testing with Job Logic ===\n\n";

// Create a mock job
$job = new App\Jobs\ProcessOrderLabelImport(
    'test-path/test.pdf',
    '2758420016283453.pdf',
    1,
    null
);

// Use reflection to test private method
$reflection = new ReflectionClass($job);
$method = $reflection->getMethod('extractOrderIdFromFilename');
$method->setAccessible(true);

$testCases = [
    ['2758420016283453.pdf', 'lazada'],
    ['Lazada_2758420016283453.pdf', 'lazada'],
    ['2758420016283453', 'lazada'],
    ['260101T6XF69GN.pdf', 'shopee'],
    ['582108769742652773.pdf', 'tiktok'],
    ['Lazada.pdf', 'lazada'], // Should return null
];

foreach ($testCases as $case) {
    list($filename, $platform) = $case;
    $result = $method->invoke($job, $filename, $platform);

    echo "Filename: $filename (Platform: $platform)\n";
    echo "  Result: " . ($result ? $result : 'NULL') . "\n\n";
}
