<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Support\Facades\DB;

$municipalityProjects = DB::table('subay_project_profiles')
    ->select(
        DB::raw('UPPER(TRIM(city_municipality)) as municipality'),
        DB::raw('count(*) as project_count')
    )
    ->whereNotNull('city_municipality')
    ->where('city_municipality', '<>', '')
    ->groupBy(DB::raw('UPPER(TRIM(city_municipality))'))
    ->get();

echo "Municipality names from database:\n";
foreach ($municipalityProjects as $row) {
    $municipality = $row->municipality;
    $normalized = trim(preg_replace('/\s*\([^)]*\)\s*/', '', $municipality));
    echo "Original: " . $municipality . " => Normalized: " . $normalized . " (" . $row->project_count . " projects)\n";
}
