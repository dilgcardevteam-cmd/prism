<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== Activate User by Verification Token ===\n";

// Get token from command line argument or prompt
$token = $argv[1] ?? null;
if (!$token) {
    echo "Enter verification token: ";
    $token = trim(fgets(STDIN));
}

if (empty($token)) {
    echo "Error: No token provided.\n";
    exit(1);
}

$user = User::where('verification_token', $token)->first();

if (!$user) {
    echo "Error: No user found with the provided verification token.\n";
    exit(1);
}

echo "User found:\n";
echo "ID: {$user->idno}\n";
echo "Name: {$user->fname} {$user->lname}\n";
echo "Email: {$user->emailaddress}\n";
echo "Current Status: {$user->status}\n";
echo "Email Verified: " . ($user->hasVerifiedEmail() ? 'Yes' : 'No') . "\n";

if ($user->status === 'active') {
    echo "User is already active. No action needed.\n";
    exit(0);
}

if ($user->hasVerifiedEmail()) {
    echo "User's email is already verified, but status is inactive. Activating user...\n";
    $user->update(['status' => 'active', 'verification_token' => null]);
    echo "✓ User status set to 'active'.\n";
    echo "✓ Verification token cleared.\n";
} else {
    // Use the verifyEmailWithToken method
    $result = $user->verifyEmailWithToken($token);
    if ($result) {
        echo "✓ Email verified and user status set to 'active'.\n";
        echo "✓ Verification token cleared.\n";
    } else {
        echo "Error: Failed to verify email with token. User may not be inactive or token mismatch.\n";
        exit(1);
    }
}

echo "Activation completed successfully!\n";
