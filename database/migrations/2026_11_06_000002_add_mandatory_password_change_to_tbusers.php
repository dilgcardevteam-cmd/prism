<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbusers')) {
            return;
        }

        if (! Schema::hasColumn('tbusers', 'must_change_password')) {
            Schema::table('tbusers', function (Blueprint $table): void {
                $table->boolean('must_change_password')->default(false)->after('password');
            });
        }

        if (! Schema::hasColumn('tbusers', 'password_security_version')) {
            Schema::table('tbusers', function (Blueprint $table): void {
                $table->unsignedInteger('password_security_version')->default(0)->after('must_change_password');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbusers')) {
            return;
        }

        if (Schema::hasColumn('tbusers', 'password_security_version')) {
            Schema::table('tbusers', function (Blueprint $table): void {
                $table->dropColumn('password_security_version');
            });
        }

        if (Schema::hasColumn('tbusers', 'must_change_password')) {
            Schema::table('tbusers', function (Blueprint $table): void {
                $table->dropColumn('must_change_password');
            });
        }
    }
};
