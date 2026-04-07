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

echo "=== Active User Found ===\n";
echo "Email: " . $user->emailaddress . "\n";
echo "Username: " . $user->username . "\n";
echo "\n=== Testing Common Passwords ===\n";

$commonPasswords = [
    'password',
    'password123',
    '12345678',
    'testpassword',
    'admin123',
    'bdferreol',  // username as password
];

foreach ($commonPasswords as $pass) {
    $check = Hash::check($pass, $user->password);
    echo ($check ? "✅" : "❌") . " '$pass'" . ($check ? " ← THIS IS THE PASSWORD!" : "") . "\n";
}

echo "\n=== SOLUTION ===\n";
echo "If none of the above passwords work, you need to:\n";
echo "1. Set a new password using Tinker:\n";
echo "   php artisan tinker\n";
echo "   > \$user = App\\Models\\User::find(75);\n";
echo "   > \$user->password = Hash::make('newpassword123');\n";
echo "   > \$user->save();\n";
echo "   > exit\n";
echo "\n2. Then login with email: " . $user->emailaddress . "\n";
echo "   And password: newpassword123\n";
