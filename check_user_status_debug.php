<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use App\Models\User;

// Check all users and their status
$users = User::select('idno', 'fname', 'lname', 'emailaddress', 'username', 'status', 'email_verified_at')->get();

echo "=== All Users Status ===\n";
echo str_pad("ID", 5) . " | " . str_pad("Name", 20) . " | " . str_pad("Username", 15) . " | " . str_pad("Status", 10) . " | Verified\n";
echo str_repeat("-", 80) . "\n";

foreach ($users as $user) {
    $verified = $user->email_verified_at ? "YES" : "NO";
    echo str_pad($user->idno, 5) . " | " . str_pad($user->fname . ' ' . $user->lname, 20) . " | " . str_pad($user->username ?? 'N/A', 15) . " | " . str_pad($user->status, 10) . " | " . $verified . "\n";
}

echo "\n\n=== To fix a user status ===\n";
echo "Run: php artisan tinker\n";
echo "> \$user = App\\Models\\User::find(ID);\n";
echo "> \$user->update(['status' => 'active']);\n";
