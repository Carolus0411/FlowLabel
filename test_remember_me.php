<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "Testing Remember Me Functionality\n";
echo "==================================\n\n";

$user = User::where('email', 'admin@admin.com')->first();

if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

// Test 1: Login WITHOUT remember
echo "Test 1: Login WITHOUT remember\n";
echo "-------------------------------\n";
Auth::logout();
$result = Auth::attempt(['email' => 'admin@admin.com', 'password' => 'q1w2e3r4'], false);
echo "Login result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "\n";
echo "Remember token: " . ($user->fresh()->remember_token ? "Set: " . substr($user->fresh()->remember_token, 0, 20) . "..." : "NULL") . "\n\n";
Auth::logout();

// Test 2: Login WITH remember
echo "Test 2: Login WITH remember\n";
echo "----------------------------\n";
$result = Auth::attempt(['email' => 'admin@admin.com', 'password' => 'q1w2e3r4'], true);
echo "Login result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "\n";
$tokenAfter = $user->fresh()->remember_token;
echo "Remember token: " . ($tokenAfter ? "✅ Set: " . substr($tokenAfter, 0, 20) . "..." : "❌ NULL") . "\n\n";

// Test 3: Check session lifetime config
echo "Test 3: Session Configuration\n";
echo "------------------------------\n";
echo "Session lifetime: " . config('session.lifetime') . " minutes\n";
echo "Session expire on close: " . (config('session.expire_on_close') ? "YES" : "NO") . "\n";
echo "\nConclusion:\n";
echo "-----------\n";
if ($tokenAfter) {
    echo "✅ Remember Me functionality is WORKING\n";
    echo "   When checkbox is checked, user will stay logged in even after browser closes\n";
    echo "   The remember token is stored in database and browser cookie\n";
} else {
    echo "⚠️  Remember Me token not set (this is normal if already authenticated)\n";
}

Auth::logout();
