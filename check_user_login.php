<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "Checking User Authentication\n";
echo "============================\n\n";

// Check if user exists
$email = 'admin@admin.com';
$user = User::where('email', $email)->first();

if ($user) {
    echo "✓ User found\n";
    echo "Email: {$user->email}\n";
    echo "Name: {$user->name}\n";
    echo "ID: {$user->id}\n";
    echo "Created: {$user->created_at}\n\n";

    // Test password
    $testPassword = 'q1w2e3r4';
    echo "Testing password: $testPassword\n";

    if (Hash::check($testPassword, $user->password)) {
        echo "✓ Password is correct!\n\n";
    } else {
        echo "✗ Password is INCORRECT!\n";
        echo "Current hash: {$user->password}\n\n";

        // Generate new hash
        echo "If you want to reset password, use this hash:\n";
        echo Hash::make($testPassword) . "\n\n";
    }

    // Check user roles
    echo "Roles:\n";
    foreach ($user->roles as $role) {
        echo "  - {$role->name}\n";
    }

    if ($user->roles->isEmpty()) {
        echo "  ⚠ No roles assigned\n";
    }

} else {
    echo "✗ User not found: $email\n";
    echo "\nAvailable users:\n";

    $users = User::all();
    foreach ($users as $u) {
        echo "  - {$u->email} (ID: {$u->id})\n";
    }
}
