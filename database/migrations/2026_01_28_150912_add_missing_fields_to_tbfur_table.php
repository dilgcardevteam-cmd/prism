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
            $table->string('barangay')->nullable();
            $table->decimal('allocation', 15, 2)->nullable();
            $table->decimal('contract_amount', 15, 2)->nullable();
            $table->string('project_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbfur', function (Blueprint $table) {
            //
        });
    }
};
