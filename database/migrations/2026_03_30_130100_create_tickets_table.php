<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tickets')) {
            return;
        }

        Schema::create('tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ticket_number')->nullable()->unique();
            $table->string('title');
            $table->text('description');
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $table->string('subcategory')->nullable();
            $table->string('priority', 30);
            $table->string('status', 60);
            $table->string('current_level', 40);
            $table->string('assigned_role', 60)->nullable();
            $table->string('contact_information', 255)->nullable();
            $table->string('region_scope', 120)->nullable();
            $table->string('province_scope', 120)->nullable();
            $table->string('office_scope', 180)->nullable();
            $table->unsignedInteger('submitted_by');
            $table->unsignedInteger('assigned_to')->nullable();
            $table->text('escalation_reason')->nullable();
            $table->unsignedInteger('escalated_by')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->boolean('forwarded_to_central_office')->default(false);
            $table->unsignedInteger('forwarded_by')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('date_submitted')->nullable();
            $table->timestamp('last_status_changed_at')->nullable();
            $table->timestamps();

            $table->foreign('submitted_by')->references('idno')->on('tbusers')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('idno')->on('tbusers')->nullOnDelete();
            $table->foreign('escalated_by')->references('idno')->on('tbusers')->nullOnDelete();
            $table->foreign('forwarded_by')->references('idno')->on('tbusers')->nullOnDelete();
            $table->foreign('resolved_by')->references('idno')->on('tbusers')->nullOnDelete();

            $table->index(['status', 'current_level']);
            $table->index(['province_scope', 'current_level']);
            $table->index(['region_scope', 'current_level']);
            $table->index('submitted_by');
            $table->index('assigned_to');
            $table->index('date_submitted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
