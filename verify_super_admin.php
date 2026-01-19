<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Super Admin Verification ===\n\n";

$superAdmin = User::where('email', 'superadmin@labsysflow.com')->first();

if ($superAdmin) {
    echo "✓ Super Admin found!\n";
    echo "  ID: {$superAdmin->id}\n";
    echo "  Name: {$superAdmin->name}\n";
    echo "  Email: {$superAdmin->email}\n";
    echo "  Status: {$superAdmin->status}\n";
    echo "\nRoles:\n";
    foreach ($superAdmin->roles as $role) {
        echo "  - {$role->name}\n";
    }
    
    echo "\nPermissions count: " . $superAdmin->getAllPermissions()->count() . "\n";
    echo "\nSample Permissions (first 10):\n";
    foreach ($superAdmin->getAllPermissions()->take(10) as $permission) {
        echo "  - {$permission->name}\n";
    }
    
    echo "\nCan assign roles: " . ($superAdmin->can('assign roles') ? 'YES' : 'NO') . "\n";
    echo "Has Super Admin role: " . ($superAdmin->hasRole('Super Admin') ? 'YES' : 'NO') . "\n";
} else {
    echo "✗ Super Admin not found!\n";
}
