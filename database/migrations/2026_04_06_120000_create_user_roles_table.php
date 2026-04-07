<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_key')->unique();
            $table->string('label')->unique();
            $table->string('base_role');
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index('base_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
