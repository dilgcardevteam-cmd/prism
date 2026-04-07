<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Debug Login System ===\n\n";

// Test 1: Find user by username
$username = 'bdferreol';
echo "Test 1: Looking for user with username: '$username'\n";
$user = User::where('username', $username)->first();

if (!$user) {
    echo "❌ User NOT found with username '$username'\n";
    echo "\nAvailable users:\n";
    $allUsers = User::select('idno', 'fname', 'lname', 'username', 'emailaddress', 'status')->get();
    foreach ($allUsers as $u) {
        echo "  - ID: {$u->idno}, Username: {$u->username}, Name: {$u->fname} {$u->lname}, Status: {$u->status}\n";
    }
    exit;
}

echo "✅ User found!\n";
echo "  ID: {$user->idno}\n";
echo "  Name: {$user->fname} {$user->lname}\n";
echo "  Username: {$user->username}\n";
echo "  Status: {$user->status}\n";
echo "  Password Hash: " . substr($user->password, 0, 20) . "...\n\n";

// Test 2: Check status
echo "Test 2: Checking status\n";
if (strtolower($user->status) === 'active') {
    echo "✅ Status is 'active'\n\n";
} else {
    echo "❌ Status is NOT 'active' (it is: '{$user->status}')\n\n";
}

// Test 3: Test password
echo "Test 3: Testing password 'Test@1234'\n";
$testPassword = 'Test@1234';
$matches = Hash::check($testPassword, $user->password);

if ($matches) {
    echo "✅ Password matches!\n\n";
} else {
    echo "❌ Password does NOT match\n";
    echo "   Password hash: {$user->password}\n";
    echo "   Testing password: '$testPassword'\n\n";
    
    // Try to set a new password
    echo "Setting new password...\n";
    $user->password = Hash::make($testPassword);
    $user->save();
    
    // Verify it was saved
    $user->refresh();
    $newMatch = Hash::check($testPassword, $user->password);
    if ($newMatch) {
        echo "✅ New password set and verified!\n";
    } else {
        echo "❌ Password still doesn't match after setting\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Username: {$user->username}\n";
echo "Password: Test@1234\n";
echo "Status: {$user->status}\n";
