<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        Schema::table('subay_project_profiles', function (Blueprint $table) {
            $columns = [
                'uploaded_images_w_geotag',
                'uploaded_images_without_geotag',
                'before_w_geotag',
                'before_without_geotag',
                'project_billboard_w_geotag',
                'project_billboard_without_geotag',
                'photo_20_40_w_geotag',
                'photo_20_40_without_geotag',
                'photo_50_70_w_geotag',
                'photo_50_70_without_geotag',
                'photo_90_w_geotag',
                'photo_90_without_geotag',
                'completed_w_geotag',
                'completed_without_geotag',
                'during_the_operation_w_geotag',
                'during_the_operation_without_geotag',
            ];

            foreach ($columns as $column) {
                if (!Schema::hasColumn('subay_project_profiles', $column)) {
                    $table->text($column)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        Schema::table('subay_project_profiles', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach ([
                'uploaded_images_w_geotag',
                'uploaded_images_without_geotag',
                'before_w_geotag',
                'before_without_geotag',
                'project_billboard_w_geotag',
                'project_billboard_without_geotag',
                'photo_20_40_w_geotag',
                'photo_20_40_without_geotag',
                'photo_50_70_w_geotag',
                'photo_50_70_without_geotag',
                'photo_90_w_geotag',
                'photo_90_without_geotag',
                'completed_w_geotag',
                'completed_without_geotag',
                'during_the_operation_w_geotag',
                'during_the_operation_without_geotag',
            ] as $column) {
                if (Schema::hasColumn('subay_project_profiles', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
