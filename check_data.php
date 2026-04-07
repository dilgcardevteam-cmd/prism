<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking LFP project:\n";
$lfp = DB::table('locally_funded_projects')
    ->where('province', 'Mountain Province')
    ->where('city_municipality', 'Bauko')
    ->first();

if ($lfp) {
    echo "Found LFP project:\n";
    echo "ID: " . $lfp->id . "\n";
    echo "Project Code: " . $lfp->subaybayan_project_code . "\n";
    echo "Province: " . $lfp->province . "\n";
    echo "City/Municipality: " . $lfp->city_municipality . "\n";
    echo "Implementing Unit: " . $lfp->implementing_unit . "\n";
    echo "Project Name: " . $lfp->project_name . "\n";

    // Check if FUR exists
    $fur = DB::table('tbfur')->where('project_code', $lfp->subaybayan_project_code)->first();
    if ($fur) {
        echo "Corresponding FUR exists:\n";
        echo "FUR Province: " . $fur->province . "\n";
        echo "FUR Implementing Unit: " . $fur->implementing_unit . "\n";
    } else {
        echo "No corresponding FUR found.\n";
    }
} else {
    echo "No LFP project found.\n";
}

echo "\nChecking LGU user:\n";
$user = DB::table('tbusers')->where('agency', 'LGU')->first();
if ($user) {
    echo "User Province: " . $user->province . "\n";
    echo "User Office: " . $user->office . "\n";
} else {
    echo "No LGU user found.\n";
}
