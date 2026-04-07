<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== User Status Check ===\n";
echo "Total users: " . User::count() . "\n";
echo "Active users: " . User::where('status', 'active')->count() . "\n";
echo "Inactive users: " . User::where('status', 'inactive')->count() . "\n";
echo "Verified users: " . User::whereNotNull('email_verified_at')->count() . "\n";

$inactiveUsers = User::where('status', 'inactive')->get();
if ($inactiveUsers->count() > 0) {
    echo "\nInactive users:\n";
    foreach ($inactiveUsers as $user) {
        echo "- {$user->fname} {$user->lname} ({$user->emailaddress})\n";
    }
}
