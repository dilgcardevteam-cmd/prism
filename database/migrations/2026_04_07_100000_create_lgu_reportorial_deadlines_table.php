<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lgu_reportorial_deadlines')) {
            Schema::create('lgu_reportorial_deadlines', function (Blueprint $table) {
                $table->id();
                $table->string('aspect', 100);
                $table->string('timeline', 30);
                $table->unsignedSmallInteger('reporting_year');
                $table->string('reporting_period', 20);
                $table->date('deadline_date');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(
                    ['aspect', 'reporting_year', 'reporting_period'],
                    'lgu_reportorial_deadlines_aspect_year_period_unique'
                );
                $table->index(['timeline', 'reporting_year'], 'lgu_reportorial_deadlines_timeline_year_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lgu_reportorial_deadlines');
    }
};
