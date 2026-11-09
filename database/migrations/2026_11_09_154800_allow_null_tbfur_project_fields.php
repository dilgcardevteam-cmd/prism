<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbfur', function (Blueprint $table) {
            $table->string('province')->nullable()->change();
            $table->string('implementing_unit')->nullable()->change();
            $table->string('fund_source')->nullable()->change();
            $table->year('funding_year')->nullable()->change();
            $table->text('project_title')->nullable()->change();
            $table->string('barangay')->nullable()->change();
            $table->decimal('allocation', 15, 2)->nullable()->change();
            $table->decimal('contract_amount', 15, 2)->nullable()->change();
            $table->string('project_status')->nullable()->change();
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tbfur', function (Blueprint $table) {
            $table->string('province')->nullable(false)->change();
            $table->string('implementing_unit')->nullable(false)->change();
            $table->string('fund_source')->nullable(false)->change();
            $table->year('funding_year')->nullable(false)->change();
            $table->text('project_title')->nullable(false)->change();
            $table->string('barangay')->nullable()->change();
            $table->decimal('allocation', 15, 2)->nullable()->change();
            $table->decimal('contract_amount', 15, 2)->nullable()->change();
            $table->string('project_status')->nullable()->change();
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });
    }
};
