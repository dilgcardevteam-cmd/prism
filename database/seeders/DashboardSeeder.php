<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        // Test data for perf testing (1000 projects)
        $provinces = ['Abra', 'Apayao', 'Benguet', 'Ifugao', 'Kalinga', 'Mountain Province'];
        $statuses = ['Completed', 'On-going', 'Not Yet Started', 'Terminated'];
        $fundSources = ['SBDP', 'FALGU', 'CMGP', 'GEF'];

        $projects = [];
        for ($i = 1; $i <= 1000; $i++) {
            $projects[] = [
                'project_code' => 'PROJ-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'project_title' => 'Test Project ' . $i,
                'province' => $provinces[array_rand($provinces)],
                'city_municipality' => 'Test City ' . rand(1, 50),
                'funding_year' => rand(2022, 2025),
                'program' => $fundSources[array_rand($fundSources)],
                'status' => $statuses[array_rand($statuses)],
                'national_subsidy_original_allocation' => rand(1000000, 50000000),
                'obligation' => rand(500000, 45000000),
                'disbursement' => rand(0, 45000000),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('subay_project_profiles')->insert($projects);
    }
}

