<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblnadai_management_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('tblnadai_management_documents', 'confirmation_accepted_by')) {
                $table->unsignedBigInteger('confirmation_accepted_by')->nullable()->after('uploaded_at');
            }

            if (!Schema::hasColumn('tblnadai_management_documents', 'confirmation_accepted_at')) {
                $table->timestamp('confirmation_accepted_at')->nullable()->after('confirmation_accepted_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tblnadai_management_documents', function (Blueprint $table) {
            if (Schema::hasColumn('tblnadai_management_documents', 'confirmation_accepted_at')) {
                $table->dropColumn('confirmation_accepted_at');
            }

            if (Schema::hasColumn('tblnadai_management_documents', 'confirmation_accepted_by')) {
                $table->dropColumn('confirmation_accepted_by');
            }
        });
    }
};
