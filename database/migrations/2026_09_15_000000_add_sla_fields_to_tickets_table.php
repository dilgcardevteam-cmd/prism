<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets') || Schema::hasColumn('tickets', 'response_due_at')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('impact', 30)->nullable();
            $table->string('urgency', 30)->nullable();
            $table->timestamp('response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->index(['status', 'resolution_due_at']);
            $table->index(['assigned_to', 'resolution_due_at']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tickets') || !Schema::hasColumn('tickets', 'response_due_at')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex(['status', 'resolution_due_at']);
            $table->dropIndex(['assigned_to', 'resolution_due_at']);
            $table->dropColumn([
                'impact',
                'urgency',
                'response_due_at',
                'resolution_due_at',
                'response_at',
            ]);
        });
    }
};