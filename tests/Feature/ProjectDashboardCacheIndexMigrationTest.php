<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectDashboardCacheIndexMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('subay_project_profiles');
        Schema::create('subay_project_profiles', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_migration_indexes_dashboard_cache_version_lookup(): void
    {
        $migration = require database_path('migrations/2026_10_28_161000_add_project_dashboard_cache_index.php');
        $migration->up();

        $this->assertTrue(Schema::hasIndex('subay_project_profiles', 'subay_profiles_created_at_idx'));
    }
}
