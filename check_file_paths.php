<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrderLabel;
use Illuminate\Support\Facades\Storage;

echo "=== File Path Debug ===\n\n";

$labels = OrderLabel::take(3)->get();

foreach ($labels as $label) {
    echo "ID: {$label->id}\n";
    echo "File path (DB): {$label->file_path}\n";
    echo "Storage exists: " . (Storage::exists($label->file_path) ? 'YES' : 'NO') . "\n";
    echo "Storage path: " . Storage::path($label->file_path) . "\n";
    echo "File exists: " . (file_exists(Storage::path($label->file_path)) ? 'YES' : 'NO') . "\n";

    // Try with disk('public')
    echo "Storage::disk('public')->exists: " . (Storage::disk('public')->exists($label->file_path) ? 'YES' : 'NO') . "\n";
    echo "Storage::disk('public')->path: " . Storage::disk('public')->path($label->file_path) . "\n";

    echo "\n";
}
