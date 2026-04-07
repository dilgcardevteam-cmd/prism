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
        Schema::table('tbusers', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
            $table->string('status')->default('inactive')->change();
            $table->string('access')->default('none')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbusers', function (Blueprint $table) {
            //
        });
    }
};
