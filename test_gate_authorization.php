<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Gate;

echo "=== Gate Authorization Test ===\n\n";

$superAdmin = User::where('email', 'superadmin@labsysflow.com')->first();
$admin = User::where('email', 'admin@gmail.com')->first();

if ($superAdmin) {
    echo "Super Admin User:\n";
    echo "  Email: {$superAdmin->email}\n";
    echo "  Roles: " . $superAdmin->roles->pluck('name')->join(', ') . "\n";

    // Test some permissions
    auth()->setUser($superAdmin);

    $testPermissions = [
        'view users',
        'create users',
        'delete users',
        'view order-label',
        'manage system',
        'view three-pl',
        'update general-setting',
    ];

    echo "\nPermission Tests:\n";
    foreach ($testPermissions as $permission) {
        $canDo = Gate::allows($permission);
        echo "  " . ($canDo ? '✓' : '✗') . " {$permission}\n";
    }
}

echo "\n---\n\n";

if ($admin) {
    echo "Admin User:\n";
    echo "  Email: {$admin->email}\n";
    echo "  Role: {$admin->role}\n";

    // Test admin user
    auth()->setUser($admin);

    echo "\nPermission Tests:\n";
    foreach ($testPermissions as $permission) {
        $canDo = Gate::allows($permission);
        echo "  " . ($canDo ? '✓' : '✗') . " {$permission}\n";
    }
}

echo "\n=== Test Complete ===\n";
