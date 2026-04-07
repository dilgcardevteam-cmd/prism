<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('emailaddress', 'bdferreol@dilg.gov.ph')->first();
if ($user) {
    echo "User found:\n";
    echo "ID: {$user->idno}\n";
    echo "Name: {$user->fname} {$user->lname}\n";
    echo "Username: {$user->username}\n";
    echo "Email: {$user->emailaddress}\n";
    echo "Status: {$user->status}\n";
    echo "Email Verified: " . ($user->hasVerifiedEmail() ? 'Yes' : 'No') . "\n";
} else {
    echo "User not found.\n";
}
