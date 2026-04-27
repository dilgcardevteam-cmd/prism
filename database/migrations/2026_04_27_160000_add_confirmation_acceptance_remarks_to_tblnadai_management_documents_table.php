<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblnadai_management_documents', function (Blueprint $table) {
            $table->text('confirmation_acceptance_remarks')
                ->nullable()
                ->after('confirmation_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('tblnadai_management_documents', function (Blueprint $table) {
            $table->dropColumn('confirmation_acceptance_remarks');
        });
    }
};
