<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "Testing Login Credentials\n";
echo "=========================\n\n";

$user = User::where('email', 'admin@admin.com')->first();

if (!$user) {
    echo "❌ User with email 'admin@admin.com' NOT FOUND\n";
    echo "\nChecking alternative email...\n";
    $user = User::where('email', 'admin@gmail.com')->first();
    if ($user) {
        echo "✅ Found user with email: admin@gmail.com\n";
    } else {
        echo "❌ No admin user found\n";
        exit(1);
    }
}

echo "✅ User Found:\n";
echo "   ID: {$user->id}\n";
echo "   Email: {$user->email}\n";
echo "   Name: {$user->name}\n";
echo "   Password Hash: " . substr($user->password, 0, 30) . "...\n";

echo "\nTesting Password 'q1w2e3r4':\n";
if (Hash::check('q1w2e3r4', $user->password)) {
    echo "✅ Password is VALID\n";
} else {
    echo "❌ Password is INVALID\n";
}

echo "\nTesting Auth::attempt():\n";
if (Auth::attempt(['email' => $user->email, 'password' => 'q1w2e3r4'])) {
    echo "✅ Auth::attempt() SUCCESS\n";
} else {
    echo "❌ Auth::attempt() FAILED\n";
}
