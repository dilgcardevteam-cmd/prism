<?php

/**
 * Setup Script for Locally Funded Financial Updates Table
 * Run this script to create the locally_funded_financial_updates table
 */

require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    if (Schema::hasTable('locally_funded_financial_updates')) {
        echo "✓ Table 'locally_funded_financial_updates' already exists.\n";
        exit(0);
    }

    Schema::create('locally_funded_financial_updates', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('project_id');
        $table->integer('year');
        $table->integer('month');
        $table->decimal('obligation', 15, 2)->nullable();
        $table->decimal('disbursed_amount', 15, 2)->nullable();
        $table->decimal('reverted_amount', 15, 2)->nullable();
        $table->decimal('utilization_rate', 5, 2)->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->timestamps();

        $table->foreign('project_id')
            ->references('id')
            ->on('locally_funded_projects')
            ->onDelete('cascade');
        $table->index(['project_id', 'year', 'month']);
    });

    echo "✓ Table 'locally_funded_financial_updates' created successfully!\n";
} catch (\Exception $e) {
    echo "✗ Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}
