<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tblpmc_document_files')) {
            Schema::table('tblpmc_document_files', function (Blueprint $table): void {
                if (!Schema::hasColumn('tblpmc_document_files', 'workflow_status')) {
                    $table->string('workflow_status')->nullable();
                }
                if (!Schema::hasColumn('tblpmc_document_files', 'workflow_actor_id')) {
                    $table->unsignedBigInteger('workflow_actor_id')->nullable();
                }
                if (!Schema::hasColumn('tblpmc_document_files', 'workflow_updated_at')) {
                    $table->timestamp('workflow_updated_at')->nullable();
                }
                if (!Schema::hasColumn('tblpmc_document_files', 'workflow_previous_status')) {
                    $table->string('workflow_previous_status')->nullable();
                }
                if (!Schema::hasColumn('tblpmc_document_files', 'workflow_remarks')) {
                    $table->text('workflow_remarks')->nullable();
                }
            });
        }

        if (Schema::hasTable('tblpmc_document_files') && Schema::hasTable('tblpmc_documents')) {
            $legacyDocuments = DB::table('tblpmc_documents as documents')
                ->whereNotNull('documents.file_path')
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('tblpmc_document_files as files')
                        ->whereColumn('files.lpmc_document_id', 'documents.id')
                        ->whereColumn('files.file_path', 'documents.file_path');
                })
                ->get([
                    'documents.id',
                    'documents.file_path',
                    'documents.original_filename',
                    'documents.uploaded_by',
                    'documents.uploaded_at',
                    'documents.created_at',
                    'documents.updated_at',
                ]);

            foreach ($legacyDocuments as $document) {
                DB::table('tblpmc_document_files')->insert([
                    'lpmc_document_id' => $document->id,
                    'file_path' => $document->file_path,
                    'original_filename' => $document->original_filename,
                    'uploaded_by' => $document->uploaded_by,
                    'uploaded_at' => $document->uploaded_at,
                    'created_at' => $document->created_at ?? now(),
                    'updated_at' => $document->updated_at ?? now(),
                ]);
            }
        }

        if (!Schema::hasTable('tblpmc_document_file_workflow_logs')) {
            Schema::create('tblpmc_document_file_workflow_logs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('lpmc_document_file_id');
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('action');
                $table->string('previous_status')->nullable();
                $table->string('resulting_status');
                $table->text('remarks')->nullable();
                $table->string('previous_file_path')->nullable();
                $table->string('previous_filename')->nullable();
                $table->timestamps();

                $table->foreign('lpmc_document_file_id', 'tblpmc_file_workflow_logs_file_fk')
                    ->references('id')
                    ->on('tblpmc_document_files')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tblpmc_document_file_workflow_logs');

        if (!Schema::hasTable('tblpmc_document_files')) {
            return;
        }

        Schema::table('tblpmc_document_files', function (Blueprint $table): void {
            $columns = [
                'workflow_status',
                'workflow_actor_id',
                'workflow_updated_at',
                'workflow_previous_status',
                'workflow_remarks',
            ];
            $existingColumns = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('tblpmc_document_files', $column)
            ));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
