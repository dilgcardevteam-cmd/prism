<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('emailaddress', 'bdferreol@dilg.gov.ph')->first();

if (!$user) {
    echo "User not found\n";
    exit;
}

echo "User: " . $user->fname . " " . $user->lname . "\n";
echo "Email: " . $user->emailaddress . "\n";
echo "ID: " . $user->idno . "\n";
echo "Status: " . $user->status . "\n";
echo "Token: " . $user->verification_token . "\n";
echo "Email Verified At: " . $user->email_verified_at . "\n";
