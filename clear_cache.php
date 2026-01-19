<?php
/**
 * Clear all Laravel caches
 * Run this on Ubuntu server after deployment: php clear_cache.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "Clearing caches...\n\n";

// Clear all caches
$commands = [
    'config:clear' => 'Configuration cache',
    'cache:clear' => 'Application cache',
    'route:clear' => 'Route cache',
    'view:clear' => 'Blade view cache',
    'event:clear' => 'Event cache',
];

foreach ($commands as $command => $description) {
    echo "Clearing {$description}...\n";
    $kernel->call($command);
}

echo "\n✅ All caches cleared successfully!\n";
echo "\nPlease refresh your browser and hard-reload (Ctrl+Shift+R or Cmd+Shift+R)\n";
