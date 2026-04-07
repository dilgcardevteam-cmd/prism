<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     EMAIL VERIFICATION TROUBLESHOOTING DIAGNOSTIC               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Check what users have pending verification
echo "1. USERS WITH INACTIVE STATUS (Pending Verification):\n";
echo "─────────────────────────────────────────────────────────────────\n";

$inactiveUsers = App\Models\User::where('status', 'inactive')->get();

if ($inactiveUsers->count() === 0) {
    echo "   ✓ No users with 'inactive' status (all users verified!)\n";
} else {
    echo "   Found " . $inactiveUsers->count() . " users waiting for verification:\n\n";
    
    foreach ($inactiveUsers as $user) {
        echo "   ┌─ User: " . $user->fname . " " . $user->lname . "\n";
        echo "   │  ID: " . $user->idno . "\n";
        echo "   │  Email: " . $user->emailaddress . "\n";
        echo "   │  Status: " . $user->status . " (need to verify)\n";
        echo "   │  Email Verified: " . ($user->hasVerifiedEmail() ? 'YES' : 'NO') . "\n";
        
        if (!is_null($user->verification_token)) {
            echo "   │  Token: " . substr($user->verification_token, 0, 20) . "...\n";
            echo "   │  Verification Link:\n";
            echo "   │  http://localhost:8000/email/verify/token/" . $user->verification_token . "\n";
        } else {
            echo "   │  Token: ✗ NOT SET (email won't verify)\n";
        }
        
        echo "   └─ Status: NEEDS VERIFICATION\n\n";
    }
}

// Check users that are already active
echo "2. USERS WITH ACTIVE STATUS (Already Verified):\n";
echo "─────────────────────────────────────────────────────────────────\n";

$activeUsers = App\Models\User::where('status', 'active')->get();

if ($activeUsers->count() === 0) {
    echo "   No active users yet\n";
} else {
    echo "   Found " . $activeUsers->count() . " verified users:\n\n";
    
    foreach ($activeUsers as $user) {
        echo "   ✓ " . $user->fname . " " . $user->lname . " (" . $user->emailaddress . ")\n";
        echo "     Verified at: " . ($user->email_verified_at ?? 'N/A') . "\n\n";
    }
}

// Check if there are issues
echo "3. POTENTIAL ISSUES CHECK:\n";
echo "─────────────────────────────────────────────────────────────────\n";

$issues = [];

// Issue 1: Users with token but status is active
$tokenButActive = App\Models\User::whereNotNull('verification_token')
    ->where('status', 'active')
    ->get();

if ($tokenButActive->count() > 0) {
    echo "   ⚠️  Issue Found: Users with token but status='active'\n";
    echo "       Count: " . $tokenButActive->count() . "\n";
    echo "       These users should have their tokens cleared.\n\n";
    $issues[] = "orphaned_tokens";
}

// Issue 2: Users with email_verified_at but token still exists
$verifiedWithToken = App\Models\User::whereNotNull('email_verified_at')
    ->whereNotNull('verification_token')
    ->get();

if ($verifiedWithToken->count() > 0) {
    echo "   ⚠️  Issue Found: Users verified but still have token\n";
    echo "       Count: " . $verifiedWithToken->count() . "\n";
    echo "       These tokens should be cleared after verification.\n\n";
    $issues[] = "orphaned_tokens";
}

// Issue 3: Users with inactive status but email_verified_at is set
$verifiedButInactive = App\Models\User::where('status', 'inactive')
    ->whereNotNull('email_verified_at')
    ->get();

if ($verifiedButInactive->count() > 0) {
    echo "   ⚠️  Issue Found: Email verified but status='inactive'\n";
    echo "       Count: " . $verifiedButInactive->count() . "\n";
    echo "       Status should change to 'active' after verification.\n\n";
    $issues[] = "inactive_after_verify";
}

if (empty($issues)) {
    echo "   ✓ No database inconsistencies detected\n\n";
}

// Check server and routes
echo "4. SYSTEM STATUS CHECK:\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Test if server is running
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if (!empty($error) || $httpCode === 0) {
    echo "   ✗ Laravel Server: NOT RUNNING\n";
    echo "     Error: Unable to connect to http://localhost:8000\n";
    echo "     Start server with: php artisan serve\n";
} else {
    echo "   ✓ Laravel Server: RUNNING (HTTP " . $httpCode . ")\n";
}

// Check mail configuration
echo "   Mail Driver: " . config('mail.default') . "\n";
echo "   Mail From: " . config('mail.from.address') . "\n\n";

// How to manually verify
echo "5. HOW TO MANUALLY VERIFY A USER:\n";
echo "─────────────────────────────────────────────────────────────────\n";

if ($inactiveUsers->count() > 0) {
    $user = $inactiveUsers->first();
    echo "   For user: " . $user->fname . " " . $user->lname . "\n\n";
    
    echo "   Option 1: Use verification link (if token exists)\n";
    if (!is_null($user->verification_token)) {
        echo "   Click this link in browser:\n";
        echo "   http://localhost:8000/email/verify/token/" . $user->verification_token . "\n\n";
    } else {
        echo "   ✗ Token missing - regenerate first\n\n";
    }
    
    echo "   Option 2: Manually verify in database\n";
    echo "   php artisan tinker\n";
    echo "   >>> \$user = App\\Models\\User::find(" . $user->idno . ")\n";
    echo "   >>> \$user->update(['status' => 'active', 'verification_token' => null, 'email_verified_at' => now()])\n\n";
}

// Final status
echo "6. FINAL DIAGNOSIS:\n";
echo "─────────────────────────────────────────────────────────────────\n";

if (empty($issues)) {
    echo "   ✓ System is working correctly\n";
    echo "   ✓ Database is consistent\n";
    
    if ($inactiveUsers->count() === 0) {
        echo "   ✓ All users are verified and active\n";
    } else {
        echo "   ⚠️  " . $inactiveUsers->count() . " users need verification\n";
        echo "   → They need to click the verification link in their email\n";
        echo "   → Or manually verify them (see Option 2 above)\n";
    }
} else {
    echo "   ✗ Issues found:\n";
    if (in_array('orphaned_tokens', $issues)) {
        echo "     - Orphaned verification tokens exist\n";
    }
    if (in_array('inactive_after_verify', $issues)) {
        echo "     - Some users have verified email but inactive status\n";
    }
}

echo "\n";
