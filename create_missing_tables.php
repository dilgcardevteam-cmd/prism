<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create tbfur_fdp table
if (!Schema::hasTable('tbfur_fdp')) {
    Schema::create('tbfur_fdp', function (Blueprint $table) {
        $table->id();
        $table->string('project_code');
        $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
        $table->string('fdp_file_path')->nullable();
        $table->timestamps();

        $table->foreign('project_code')->references('project_code')->on('tbfur')->onDelete('cascade');
        $table->unique(['project_code', 'quarter']);
    });
    echo "Created tbfur_fdp table\n";
} else {
    echo "tbfur_fdp table already exists\n";
}

// Create tbfur_admin_remarks table
if (!Schema::hasTable('tbfur_admin_remarks')) {
    Schema::create('tbfur_admin_remarks', function (Blueprint $table) {
        $table->id();
        $table->string('project_code');
        $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
        $table->text('remarks');
        $table->unsignedBigInteger('admin_id');
        $table->timestamps();

        $table->foreign('project_code')->references('project_code')->on('tbfur')->onDelete('cascade');
        $table->foreign('admin_id')->references('idno')->on('tbusers');
        $table->unique(['project_code', 'quarter']);
    });
    echo "Created tbfur_admin_remarks table\n";
} else {
    echo "tbfur_admin_remarks table already exists\n";
}

echo "Migration complete!\n";
