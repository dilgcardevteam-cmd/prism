<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Get the active user
$user = User::where('status', 'active')->first();

if (!$user) {
    echo "❌ No active user found\n";
    exit;
}

echo "=== User Information ===\n";
echo "ID: " . $user->idno . "\n";
echo "Name: " . $user->fname . " " . $user->lname . "\n";
echo "Email: " . $user->emailaddress . "\n";
echo "Username: " . $user->username . "\n";
echo "Status: " . $user->status . "\n";
echo "Password Hash: " . $user->password . "\n";
echo "\n";

// Test password verification
$testPassword = 'password123';  // Change this to the actual password you're using

echo "=== Testing Password Verification ===\n";
echo "Testing password: '$testPassword'\n";

if (Hash::check($testPassword, $user->password)) {
    echo "✅ Password matches!\n";
} else {
    echo "❌ Password does NOT match\n";
}

echo "\n=== Database Password Check ===\n";
echo "Is password hashed? " . (strlen($user->password) > 20 ? "YES (looks like bcrypt)" : "NO (might be plaintext)") . "\n";

// Check what field the login form uses
echo "\n=== Login Field Check ===\n";
echo "The login form uses: 'emailaddress' field\n";
echo "User's emailaddress: " . $user->emailaddress . "\n";
echo "User's username: " . $user->username . "\n";
echo "\nTry logging in with email: " . $user->emailaddress . "\n";
