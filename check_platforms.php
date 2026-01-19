<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

echo "=== Platform 3PL List ===\n";
$platforms = App\Models\ThreePl::all();
foreach ($platforms as $p) {
    echo "{$p->id} - {$p->code} - {$p->name} - Active: " . ($p->is_active ? 'Yes' : 'No') . "\n";
}
echo "\n=== Total: " . $platforms->count() . " platforms ===\n";
