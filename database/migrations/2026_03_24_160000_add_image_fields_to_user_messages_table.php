<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_messages')) {
            return;
        }

        Schema::table('user_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('user_messages', 'image_path')) {
                $table->string('image_path')->nullable()->after('message');
            }

            if (!Schema::hasColumn('user_messages', 'image_original_name')) {
                $table->string('image_original_name')->nullable()->after('image_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_messages')) {
            return;
        }

        Schema::table('user_messages', function (Blueprint $table) {
            if (Schema::hasColumn('user_messages', 'image_original_name')) {
                $table->dropColumn('image_original_name');
            }

            if (Schema::hasColumn('user_messages', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }
};
