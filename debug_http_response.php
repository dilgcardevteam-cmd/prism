<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     DEBUGGING: Detailed HTTP Response Analysis                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Get pending user
$user = App\Models\User::where('status', 'inactive')->first();

if (!$user) {
    echo "No pending users\n";
    exit;
}

$token = $user->verification_token;
$url = "http://localhost:8000/email/verify/token/" . $token;

echo "Testing URL: " . $url . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);  // DON'T follow redirects
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);  // Include headers

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

// Split headers and body
$headerEnd = strpos($response, "\r\n\r\n");
$headers = substr($response, 0, $headerEnd);
$body = substr($response, $headerEnd + 4);

echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response Headers:\n";
echo $headers . "\n";

echo "\nResponse Body (first 500 chars):\n";
echo substr($body, 0, 500) . "\n\n";

// Check the database
$checkUser = App\Models\User::find($user->idno);

echo "User Status After Request:\n";
echo "  Status: " . $checkUser->status . " (should be 'active')\n";
echo "  Token: " . (is_null($checkUser->verification_token) ? 'CLEARED' : 'STILL PRESENT') . " (should be CLEARED)\n\n";

// Check logs
echo "Recent Log Entries (last 10 lines):\n";
echo "─────────────────────────────────────────────────────────────────\n";

$logFile = 'storage/logs/laravel.log';

if (file_exists($logFile)) {
    $lines = file($logFile);
    $last10 = array_slice($lines, -10);
    
    foreach ($last10 as $line) {
        echo trim($line) . "\n";
    }
} else {
    echo "Log file not found\n";
}

echo "\n";
