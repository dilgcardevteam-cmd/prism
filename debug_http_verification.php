<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║        DEBUGGING: REAL HTTP REQUEST VERIFICATION               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Create a test user
echo "1. Creating test user...\n";
$user = App\Models\User::create([
    'fname' => 'Debug',
    'lname' => 'Test',
    'agency' => 'DILG',
    'position' => 'Engineer',
    'region' => 'Cordillera',
    'province' => 'Benguet',
    'office' => 'Baguio',
    'emailaddress' => 'debug' . time() . '@example.com',
    'mobileno' => '09123456789',
    'username' => 'debugtest' . time(),
    'password' => bcrypt('password123'),
    'role' => 'user',
    'status' => 'inactive',
    'access' => 'none'
]);

// Generate token
$token = $user->generateVerificationToken();
echo "   Created user ID: " . $user->idno . "\n";
echo "   Token: " . substr($token, 0, 20) . "...\n\n";

// Check the actual database
echo "2. Checking database BEFORE verification...\n";
$dbUser = App\Models\User::find($user->idno);
echo "   Status in DB: " . $dbUser->status . "\n";
echo "   Token in DB: " . (!is_null($dbUser->verification_token) ? 'Present' : 'NULL') . "\n";
echo "   Email Verified: " . (!is_null($dbUser->email_verified_at) ? 'YES' : 'NO') . "\n\n";

// Simulate the HTTP request by calling the controller method directly
echo "3. Testing verifyWithToken() method directly (NO HTTP)...\n";

$foundUser = App\Models\User::where('verification_token', $token)->first();
if ($foundUser) {
    echo "   ✓ Token found in database\n";
    
    // Perform the same operations the controller does
    $foundUser->markEmailAsVerified();
    $foundUser->status = 'active';
    $foundUser->verification_token = null;
    $result = $foundUser->save();
    
    echo "   ✓ Marked as verified: YES\n";
    echo "   ✓ Status set to active: YES\n";
    echo "   ✓ Token cleared: YES\n";
    echo "   ✓ Saved to DB: " . ($result ? 'YES' : 'NO') . "\n\n";
} else {
    echo "   ✗ Token NOT found in database!\n\n";
}

// Check the database AFTER
echo "4. Checking database AFTER verification...\n";
$verifiedUser = App\Models\User::find($user->idno);
echo "   Status in DB: " . $verifiedUser->status . "\n";
echo "   Token in DB: " . (is_null($verifiedUser->verification_token) ? 'NULL' : 'Present') . "\n";
echo "   Email Verified: " . (!is_null($verifiedUser->email_verified_at) ? 'YES' : 'NO') . "\n\n";

// Now test via HTTP request
echo "5. Testing via REAL HTTP request...\n";
echo "   Making HTTP GET request to: http://localhost:8000/email/verify/token/" . $token . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/email/verify/token/" . $token);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "   HTTP Status Code: " . $httpCode . "\n";
if (!empty($error)) {
    echo "   ✗ Error: " . $error . "\n\n";
    echo "   ⚠️  ISSUE FOUND: Laravel server is not running!\n";
    echo "   The verification link cannot be processed without the Laravel server.\n\n";
    echo "   Solution: Start the server with:\n";
    echo "   php artisan serve\n";
} else {
    echo "   Response received (length: " . strlen($response) . " bytes)\n\n";
    
    // Check database after HTTP request
    echo "6. Checking database AFTER HTTP request...\n";
    $httpUser = App\Models\User::find($user->idno);
    echo "   Status in DB: " . $httpUser->status . "\n";
    echo "   Token in DB: " . (is_null($httpUser->verification_token) ? 'NULL' : 'Present') . "\n";
    echo "   Email Verified: " . (!is_null($httpUser->email_verified_at) ? 'YES' : 'NO') . "\n\n";
    
    if ($httpUser->status === 'active' && is_null($httpUser->verification_token)) {
        echo "✓ HTTP verification SUCCESSFUL!\n";
    } else {
        echo "✗ HTTP verification FAILED!\n";
        echo "   The request was processed but database wasn't updated.\n";
    }
}

echo "\n";
