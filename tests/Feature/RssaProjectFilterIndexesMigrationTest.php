<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RssaProjectFilterIndexesMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('rssa_project_profiles');
        Schema::create('rssa_project_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('funding_year')->nullable();
            $table->string('type_of_project')->nullable();
            $table->string('status')->nullable();
            $table->string('region')->nullable();
        });
    }

    public function test_migration_adds_indexes_for_common_project_filters(): void
    {
        $migration = require database_path('migrations/2026_10_28_160000_add_rssa_project_filter_indexes.php');
        $migration->up();

        foreach ([
            'rssa_profiles_province_city_idx',
            'rssa_profiles_funding_year_idx',
            'rssa_profiles_type_idx',
            'rssa_profiles_status_idx',
            'rssa_profiles_region_idx',
        ] as $index) {
            $this->assertTrue(Schema::hasIndex('rssa_project_profiles', $index));
        }
    }
}
