<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('locally_funded_gallery_images')) {
            $this->ensureColumnType(
                'locally_funded_gallery_images',
                'uploaded_by',
                'int(10) unsigned',
                'ALTER TABLE `locally_funded_gallery_images` MODIFY `uploaded_by` INT UNSIGNED NOT NULL'
            );

            $this->ensureForeignKey(
                'locally_funded_gallery_images',
                'locally_funded_gallery_images_project_id_foreign',
                'project_id',
                'locally_funded_projects',
                'id',
                'CASCADE'
            );

            $this->ensureForeignKey(
                'locally_funded_gallery_images',
                'locally_funded_gallery_images_uploaded_by_foreign',
                'uploaded_by',
                'tbusers',
                'idno',
                'CASCADE'
            );

            return;
        }

        Schema::create('locally_funded_gallery_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('category')->default('During'); // All, Before, Project Billboard, Community Billboard, 20-40%, 50-70%, 90%, Completed, During
            $table->string('image_path'); // Path to image in storage
            $table->unsignedInteger('uploaded_by'); // User who uploaded
            $table->decimal('latitude', 10, 8)->nullable(); // GPS latitude
            $table->decimal('longitude', 11, 8)->nullable(); // GPS longitude
            $table->decimal('accuracy', 8, 2)->nullable(); // GPS accuracy in meters
            $table->timestamps();

            // Add foreign keys
            $table->foreign('project_id')
                ->references('id')
                ->on('locally_funded_projects')
                ->onDelete('cascade');

            $table->foreign('uploaded_by')
                ->references('idno')
                ->on('tbusers')
                ->onDelete('cascade');

            // Add indexes for common queries
            $table->index('project_id');
            $table->index('category');
            $table->index('uploaded_by');
            $table->index('created_at');
            $table->index(['project_id', 'category']);
        });
    }

    private function ensureColumnType(string $table, string $column, string $expectedType, string $alterSql): void
    {
        $currentType = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->value('COLUMN_TYPE');

        if (is_string($currentType) && strtolower($currentType) !== strtolower($expectedType)) {
            DB::statement($alterSql);
        }
    }

    private function ensureForeignKey(
        string $table,
        string $constraintName,
        string $column,
        string $referencesTable,
        string $referencesColumn,
        string $onDelete
    ): void {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if (! $exists) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s',
                $table,
                $constraintName,
                $column,
                $referencesTable,
                $referencesColumn,
                $onDelete
            ));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locally_funded_gallery_images');
    }
};
