<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbfur', function (Blueprint $table) {
            if (!Schema::hasColumn('tbfur', 'implementing_unit')) {
                $table->string('implementing_unit')->after('province');
            }
            if (!Schema::hasColumn('tbfur', 'fund_source')) {
                $table->string('fund_source')->after('implementing_unit');
            }
            if (!Schema::hasColumn('tbfur', 'funding_year')) {
                $table->year('funding_year')->after('fund_source');
            }
            if (!Schema::hasColumn('tbfur', 'project_title')) {
                $table->text('project_title')->after('funding_year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbfur', function (Blueprint $table) {
            if (Schema::hasColumn('tbfur', 'project_title')) {
                $table->dropColumn('project_title');
            }
            if (Schema::hasColumn('tbfur', 'funding_year')) {
                $table->dropColumn('funding_year');
            }
            if (Schema::hasColumn('tbfur', 'fund_source')) {
                $table->dropColumn('fund_source');
            }
            if (Schema::hasColumn('tbfur', 'implementing_unit')) {
                $table->dropColumn('implementing_unit');
            }
        });
    }
};
