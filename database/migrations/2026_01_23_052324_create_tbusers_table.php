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
        if (!Schema::hasTable('tbusers')) {
            Schema::create('tbusers', function (Blueprint $table) {
                $table->id();
                $table->string('fname');
                $table->string('lname');
                $table->string('agency');
                $table->string('position');
                $table->string('region');
                $table->string('province');
                $table->string('office')->nullable();
                $table->string('emailaddress')->unique();
                $table->string('mobileno');
                $table->string('username')->unique();
                $table->string('password');
                $table->string('role')->default('user');
                $table->string('status')->default('inactive');
                $table->string('access')->default('none');
                $table->string('verification_token')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbusers');
    }
};
