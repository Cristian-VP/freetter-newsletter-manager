<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Running this migration outside a transaction is required for SQLite
     * so that we can toggle foreign key checks while recreating the table.
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE publishing_media DROP CONSTRAINT IF EXISTS publishing_media_disk_check');
            DB::statement("ALTER TABLE publishing_media ADD CONSTRAINT publishing_media_disk_check CHECK (disk IN ('local', 's3', 'public'))");

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            try {
                $this->recreateTableForSqlite(['local', 's3', 'public']);
            } finally {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE publishing_media SET disk = 'local' WHERE disk = 'public'");
            DB::statement('ALTER TABLE publishing_media DROP CONSTRAINT IF EXISTS publishing_media_disk_check');
            DB::statement("ALTER TABLE publishing_media ADD CONSTRAINT publishing_media_disk_check CHECK (disk IN ('local', 's3'))");

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            try {
                $this->recreateTableForSqlite(['local', 's3']);
            } finally {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }

    /**
     * Recreate the publishing_media table for SQLite with the given enum values.
     *
     * @param array<int, string> $diskValues
     */
    private function recreateTableForSqlite(array $diskValues): void
    {
        $oldTable = 'publishing_media';
        $newTable = 'publishing_media_new';

        Schema::create($newTable, function (Blueprint $table) use ($diskValues) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')
                ->constrained('identity_workspaces', 'id')
                ->cascadeOnDelete();
            $table->string('path');
            $table->enum('disk', $diskValues)->default('local');
            $table->string('mime_type', 255);
            $table->unsignedInteger('size_kb');
            $table->timestamps();
            $table->index(['workspace_id', 'created_at']);
            $table->index(['workspace_id', 'disk']);
        });

        DB::statement("INSERT INTO {$newTable} SELECT * FROM {$oldTable}");
        Schema::drop($oldTable);
        Schema::rename($newTable, $oldTable);
    }
};
