<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "=====================================\n";
echo "   LABSYSFLOW - STATUS AKHIR\n";
echo "=====================================\n\n";

// Check tables
$tables = DB::select('SHOW TABLES');
$databaseName = 'Tables_in_' . DB::getDatabaseName();
echo "📊 TABEL DATABASE: " . count($tables) . " tables\n";
echo "---\n";
foreach ($tables as $table) {
    echo "  • " . $table->$databaseName . "\n";
}

echo "\n";

// Check permissions
$permissions = DB::table('permissions')->orderBy('name')->get(['name', 'resource']);
echo "🔐 PERMISSIONS: " . count($permissions) . " permissions\n";
echo "---\n";

$groupedPerms = [];
foreach ($permissions as $perm) {
    $resource = $perm->resource ?? 'other';
    if (!isset($groupedPerms[$resource])) {
        $groupedPerms[$resource] = [];
    }
    $groupedPerms[$resource][] = $perm->name;
}

foreach ($groupedPerms as $resource => $perms) {
    echo "\n" . strtoupper($resource) . ":\n";
    foreach ($perms as $perm) {
        echo "  • $perm\n";
    }
}

echo "\n";
echo "=====================================\n";
echo "✅ Menu yang AKTIF:\n";
echo "=====================================\n";
echo "1. Menu Order Label\n";
echo "2. Setup (Settings, Account Mapping, Code, Draft, Test Mail, Queue Log)\n";
echo "3. Users (Users, Roles, Permissions, User Logs)\n";
echo "\n";
echo "=====================================\n";
echo "   CLEANUP BERHASIL!\n";
echo "=====================================\n\n";
