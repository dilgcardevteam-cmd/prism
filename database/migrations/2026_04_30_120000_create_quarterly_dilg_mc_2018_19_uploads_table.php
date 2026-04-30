<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quarterly_dilg_mc_2018_19_uploads')) {
            Schema::create('quarterly_dilg_mc_2018_19_uploads', function (Blueprint $table) {
                $table->id();
                $table->string('office');
                $table->string('province');
                $table->unsignedSmallInteger('year');
                $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
                $table->string('file_path');
                $table->string('original_name')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamp('uploaded_at')->nullable();
                $table->timestamps();

                $table->index(['office', 'year', 'quarter'], 'q_dilg_mc_2018_19_office_year_quarter_idx');
            });
        }

        if (Schema::hasTable('tblroad_maintenance_status_documents')) {
            $legacyRows = DB::table('tblroad_maintenance_status_documents')
                ->where('doc_type', 'dilg_mc_2018_19')
                ->orderBy('id')
                ->get([
                    'office',
                    'province',
                    'year',
                    'quarter',
                    'file_path',
                    'uploaded_by',
                    'uploaded_at',
                    'created_at',
                    'updated_at',
                ]);

            foreach ($legacyRows as $row) {
                $exists = DB::table('quarterly_dilg_mc_2018_19_uploads')
                    ->where('office', $row->office)
                    ->where('year', $row->year)
                    ->where('quarter', $row->quarter)
                    ->where('file_path', $row->file_path)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('quarterly_dilg_mc_2018_19_uploads')->insert([
                    'office' => $row->office,
                    'province' => $row->province,
                    'year' => $row->year,
                    'quarter' => $row->quarter,
                    'file_path' => $row->file_path,
                    'original_name' => $row->file_path ? basename((string) $row->file_path) : null,
                    'uploaded_by' => $row->uploaded_by,
                    'uploaded_at' => $row->uploaded_at,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quarterly_dilg_mc_2018_19_uploads');
    }
};
