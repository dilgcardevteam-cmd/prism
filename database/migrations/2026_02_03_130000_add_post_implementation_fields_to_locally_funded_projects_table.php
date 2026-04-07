<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = 'locally_funded_projects';

        $this->addColumnIfMissing($tableName, 'pcr_submission_deadline', function (Blueprint $table) {
            $table->date('pcr_submission_deadline')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'pcr_submission_deadline_updated_at', function (Blueprint $table) {
            $table->timestamp('pcr_submission_deadline_updated_at')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'pcr_submission_deadline_updated_by', function (Blueprint $table) {
            $table->unsignedBigInteger('pcr_submission_deadline_updated_by')->nullable();
        });

        $this->addColumnIfMissing($tableName, 'pcr_date_submitted_to_po', function (Blueprint $table) {
            $table->date('pcr_date_submitted_to_po')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'pcr_date_submitted_to_po_updated_at', function (Blueprint $table) {
            $table->timestamp('pcr_date_submitted_to_po_updated_at')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'pcr_date_submitted_to_po_updated_by', function (Blueprint $table) {
            $table->unsignedBigInteger('pcr_date_submitted_to_po_updated_by')->nullable();
        });

        $this->addColumnIfMissing($tableName, 'pcr_date_received_by_ro', function (Blueprint $table) {
            $table->date('pcr_date_received_by_ro')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'pcr_date_received_by_ro_updated_at', function (Blueprint $table) {
            $table->timestamp('pcr_date_received_by_ro_updated_at')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'pcr_date_received_by_ro_updated_by', function (Blueprint $table) {
            $table->unsignedBigInteger('pcr_date_received_by_ro_updated_by')->nullable();
        });

        $this->addColumnIfMissing($tableName, 'pcr_remarks', function (Blueprint $table) {
            $table->longText('pcr_remarks')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'pcr_remarks_updated_at', function (Blueprint $table) {
            $table->timestamp('pcr_remarks_updated_at')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'pcr_remarks_updated_by', function (Blueprint $table) {
            $table->unsignedBigInteger('pcr_remarks_updated_by')->nullable();
        });

        $this->addColumnIfMissing($tableName, 'rssa_report_deadline', function (Blueprint $table) {
            $table->date('rssa_report_deadline')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_report_deadline_updated_at', function (Blueprint $table) {
            $table->timestamp('rssa_report_deadline_updated_at')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_report_deadline_updated_by', function (Blueprint $table) {
            $table->unsignedBigInteger('rssa_report_deadline_updated_by')->nullable();
        });

        $this->addColumnIfMissing($tableName, 'rssa_submission_status', function (Blueprint $table) {
            $table->string('rssa_submission_status')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_submission_status_updated_at', function (Blueprint $table) {
            $table->timestamp('rssa_submission_status_updated_at')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_submission_status_updated_by', function (Blueprint $table) {
            $table->unsignedBigInteger('rssa_submission_status_updated_by')->nullable();
        });

        $this->addColumnIfMissing($tableName, 'rssa_date_submitted_to_po', function (Blueprint $table) {
            $table->date('rssa_date_submitted_to_po')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_date_submitted_to_po_updated_at', function (Blueprint $table) {
            $table->timestamp('rssa_date_submitted_to_po_updated_at')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_date_submitted_to_po_updated_by', function (Blueprint $table) {
            $table->unsignedBigInteger('rssa_date_submitted_to_po_updated_by')->nullable();
        });

        $this->addColumnIfMissing($tableName, 'rssa_date_received_by_ro', function (Blueprint $table) {
            $table->date('rssa_date_received_by_ro')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_date_received_by_ro_updated_at', function (Blueprint $table) {
            $table->timestamp('rssa_date_received_by_ro_updated_at')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_date_received_by_ro_updated_by', function (Blueprint $table) {
            $table->unsignedBigInteger('rssa_date_received_by_ro_updated_by')->nullable();
        });

        $this->addColumnIfMissing($tableName, 'rssa_date_submitted_to_co', function (Blueprint $table) {
            $table->date('rssa_date_submitted_to_co')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_date_submitted_to_co_updated_at', function (Blueprint $table) {
            $table->timestamp('rssa_date_submitted_to_co_updated_at')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_date_submitted_to_co_updated_by', function (Blueprint $table) {
            $table->unsignedBigInteger('rssa_date_submitted_to_co_updated_by')->nullable();
        });

        $this->addColumnIfMissing($tableName, 'rssa_remarks', function (Blueprint $table) {
            $table->longText('rssa_remarks')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_remarks_updated_at', function (Blueprint $table) {
            $table->timestamp('rssa_remarks_updated_at')->nullable();
        });
        $this->addColumnIfMissing($tableName, 'rssa_remarks_updated_by', function (Blueprint $table) {
            $table->unsignedBigInteger('rssa_remarks_updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = 'locally_funded_projects';
        $columns = [
            'pcr_submission_deadline',
            'pcr_submission_deadline_updated_at',
            'pcr_submission_deadline_updated_by',
            'pcr_date_submitted_to_po',
            'pcr_date_submitted_to_po_updated_at',
            'pcr_date_submitted_to_po_updated_by',
            'pcr_date_received_by_ro',
            'pcr_date_received_by_ro_updated_at',
            'pcr_date_received_by_ro_updated_by',
            'pcr_remarks',
            'pcr_remarks_updated_at',
            'pcr_remarks_updated_by',
            'rssa_report_deadline',
            'rssa_report_deadline_updated_at',
            'rssa_report_deadline_updated_by',
            'rssa_submission_status',
            'rssa_submission_status_updated_at',
            'rssa_submission_status_updated_by',
            'rssa_date_submitted_to_po',
            'rssa_date_submitted_to_po_updated_at',
            'rssa_date_submitted_to_po_updated_by',
            'rssa_date_received_by_ro',
            'rssa_date_received_by_ro_updated_at',
            'rssa_date_received_by_ro_updated_by',
            'rssa_date_submitted_to_co',
            'rssa_date_submitted_to_co_updated_at',
            'rssa_date_submitted_to_co_updated_by',
            'rssa_remarks',
            'rssa_remarks_updated_at',
            'rssa_remarks_updated_by',
        ];

        $existing = array_values(array_filter($columns, function (string $column) use ($tableName) {
            return Schema::hasColumn($tableName, $column);
        }));

        if (count($existing) > 0) {
            Schema::table($tableName, function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }

    private function addColumnIfMissing(string $tableName, string $column, callable $callback): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            Schema::table($tableName, function (Blueprint $table) use ($callback) {
                $callback($table);
            });
        }
    }
};
