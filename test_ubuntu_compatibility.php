<?php

/**
 * Test Ubuntu Compatibility for ProcessOrderLabelImport
 * This script tests key functions that differ between Windows and Ubuntu
 */

echo "=== Ubuntu Compatibility Test ===\n\n";

// 1. Test OS Detection
echo "1. Testing OS Detection:\n";
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
echo "   OS: " . PHP_OS . "\n";
echo "   Detected as: " . ($isWindows ? "Windows" : "Linux/Unix") . "\n\n";

// 2. Test Ghostscript Detection
echo "2. Testing Ghostscript Detection:\n";

function testGetGhostscriptPath(): ?string
{
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    $paths = $isWindows 
        ? [
            'gswin64c.exe',
            'gswin32c.exe',
            'C:\Program Files\gs\gs10.04.0\bin\gswin64c.exe',
            'C:\Program Files\gs\gs10.06.0\bin\gswin64c.exe',
        ]
        : [
            'gs',
            '/usr/bin/gs',
            '/usr/local/bin/gs',
        ];

    foreach ($paths as $path) {
        if (strpos($path, ':') !== false || strpos($path, '/') === 0) {
            if (file_exists($path)) {
                echo "   ✓ Found at absolute path: $path\n";
                return $path;
            }
        } else {
            $output = [];
            $returnVar = 0;
            $cmd = $isWindows ? "where $path 2>nul" : "which $path 2>/dev/null";
            exec($cmd, $output, $returnVar);
            if ($returnVar === 0 && !empty($output)) {
                echo "   ✓ Found in PATH: " . trim($output[0]) . "\n";
                return trim($output[0]);
            }
        }
    }
    return null;
}

$gsPath = testGetGhostscriptPath();
if ($gsPath) {
    echo "   Ghostscript is available: $gsPath\n";
    
    // Test Ghostscript version
    $cmd = $isWindows ? "\"$gsPath\" --version 2>&1" : escapeshellarg($gsPath) . " --version 2>&1";
    $version = shell_exec($cmd);
    echo "   Version: " . trim($version) . "\n";
} else {
    echo "   ✗ Ghostscript not found\n";
    echo "   Ubuntu installation command: sudo apt-get install ghostscript\n";
}
echo "\n";

// 3. Test Path Handling
echo "3. Testing Path Handling:\n";
$testPaths = [
    '/var/www/html/storage/app/temp/test.pdf',
    'C:\xampp\htdocs\storage\app\temp\test.pdf',
];

foreach ($testPaths as $testPath) {
    $dir = dirname($testPath);
    $base = basename($testPath);
    $filename = pathinfo($testPath, PATHINFO_FILENAME);
    
    echo "   Path: $testPath\n";
    echo "   - dirname: $dir\n";
    echo "   - basename: $base\n";
    echo "   - filename: $filename\n\n";
}

// 4. Test Command Escaping
echo "4. Testing Command Escaping:\n";

function testCommandEscape($path): string
{
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    return $isWindows ? '"' . $path . '"' : escapeshellarg($path);
}

$testFiles = [
    '/tmp/test file with spaces.pdf',
    '/tmp/test-simple.pdf',
    'C:\Temp\test file.pdf',
];

foreach ($testFiles as $file) {
    echo "   Original: $file\n";
    echo "   Escaped:  " . testCommandEscape($file) . "\n\n";
}

// 5. Test Permission Handling
echo "5. Testing Directory Permissions:\n";
$tempDir = sys_get_temp_dir() . '/labsysflow_test_' . time();

try {
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
        echo "   ✓ Created temp directory: $tempDir\n";
        echo "   Permissions: " . substr(sprintf('%o', fileperms($tempDir)), -4) . "\n";
        
        // Test file creation
        $testFile = $tempDir . '/test.txt';
        file_put_contents($testFile, 'test');
        
        if (file_exists($testFile)) {
            echo "   ✓ File creation successful\n";
            echo "   File permissions: " . substr(sprintf('%o', fileperms($testFile)), -4) . "\n";
            unlink($testFile);
        }
        
        rmdir($tempDir);
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Test Memory and Execution Limits
echo "6. Testing PHP Limits:\n";
echo "   memory_limit: " . ini_get('memory_limit') . "\n";
echo "   max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "   post_max_size: " . ini_get('post_max_size') . "\n";
echo "   upload_max_filesize: " . ini_get('upload_max_filesize') . "\n\n";

// 7. Test Required PHP Extensions
echo "7. Testing Required Extensions:\n";
$requiredExtensions = ['pdo', 'mbstring', 'fileinfo', 'json', 'zip'];

foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "   " . ($loaded ? "✓" : "✗") . " $ext\n";
}
echo "\n";

// 8. Ubuntu-specific Recommendations
if (!$isWindows) {
    echo "=== Ubuntu Setup Recommendations ===\n\n";
    echo "1. Install Ghostscript:\n";
    echo "   sudo apt-get update\n";
    echo "   sudo apt-get install ghostscript\n\n";
    
    echo "2. Set proper storage permissions:\n";
    echo "   sudo chown -R www-data:www-data storage/\n";
    echo "   sudo chmod -R 775 storage/\n\n";
    
    echo "3. Increase PHP limits in php.ini:\n";
    echo "   memory_limit = 1024M\n";
    echo "   max_execution_time = 600\n";
    echo "   post_max_size = 100M\n";
    echo "   upload_max_filesize = 100M\n\n";
    
    echo "4. Setup queue worker as systemd service:\n";
    echo "   Create: /etc/systemd/system/laravel-worker.service\n\n";
    
    echo "5. Install supervisor for queue management:\n";
    echo "   sudo apt-get install supervisor\n";
    echo "   Create config: /etc/supervisor/conf.d/labsysflow.conf\n\n";
}

echo "=== Test Complete ===\n";
