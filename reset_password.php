<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Update the password
$user = User::find(75);
$newPassword = 'Test@1234';
$user->password = Hash::make($newPassword);
$user->save();

echo "✅ Password has been reset!\n\n";
echo "=== Login Credentials ===\n";
echo "Email/Username: " . $user->emailaddress . "\n";
echo "Password: " . $newPassword . "\n";
echo "\nYou can now login with these credentials.\n";
