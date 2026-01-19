<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\ProcessOrderLabelImport;
use Illuminate\Support\Facades\Storage;

// First, let's check if we have 3PL for Lazada
$lazadaThreePl = \App\Models\ThreePl::where('name', 'like', '%Lazada%')->first();

if (!$lazadaThreePl) {
    echo "Creating Lazada 3PL record...\n";
    $lazadaThreePl = \App\Models\ThreePl::create([
        'code' => 'LAZADA',
        'name' => 'Lazada',
        'is_active' => true,
        'created_by' => 1,
        'updated_by' => 1,
    ]);
}

echo "Lazada 3PL ID: " . $lazadaThreePl->id . "\n";
echo "Lazada 3PL Name: " . $lazadaThreePl->name . "\n\n";

// Copy PDF to import-temp folder
$sourcePath = 'D:\\Lazada.pdf';
$destFilename = '2758420016283453.pdf'; // Use the order ID as filename
$tempPath = 'import-temp/' . uniqid() . '_' . $destFilename;

if (file_exists($sourcePath)) {
    Storage::put($tempPath, file_get_contents($sourcePath));
    echo "Copied PDF to: $tempPath\n\n";

    // Create and run the job
    echo "Processing PDF import...\n";
    $job = new ProcessOrderLabelImport(
        $tempPath,
        $destFilename,
        1,
        $lazadaThreePl->id
    );

    try {
        $job->handle();
        echo "\n✓ Job completed successfully!\n\n";
    } catch (\Exception $e) {
        echo "\n✗ Job failed: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n\n";
    }

    // Check the results
    echo "=== Results ===\n";
    $labels = App\Models\OrderLabel::latest()->take(5)->get();

    foreach ($labels as $label) {
        echo "ID: " . $label->id . "\n";
        echo "Code: " . $label->code . "\n";
        echo "Split Filename: " . $label->split_filename . "\n";
        echo "Original Filename: " . $label->original_filename . "\n";
        echo "3PL ID: " . $label->three_pl_id . "\n";
        echo "Batch No: " . $label->batch_no . "\n";
        echo "Note: " . $label->note . "\n";
        echo "---\n";
    }

} else {
    echo "Source PDF not found: $sourcePath\n";
}
