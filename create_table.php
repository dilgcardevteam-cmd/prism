<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Schema::create('tbfur_written_notice', function (Blueprint $table) {
    $table->id();
    $table->string('project_code');
    $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
    $table->string('notice_screenshot_path')->nullable();
    $table->string('notice_pdf_path')->nullable();
    $table->string('secretary_dbm_path')->nullable();
    $table->string('secretary_dilg_path')->nullable();
    $table->string('speaker_house_path')->nullable();
    $table->string('president_senate_path')->nullable();
    $table->string('house_committee_path')->nullable();
    $table->string('senate_committee_path')->nullable();
    $table->timestamps();

    // Temporarily remove foreign key to avoid constraint issues
    // $table->foreign('project_code')->references('project_code')->on('tbfur')->onDelete('cascade');
    $table->unique(['project_code', 'quarter']);
});

echo "Table created successfully.";
