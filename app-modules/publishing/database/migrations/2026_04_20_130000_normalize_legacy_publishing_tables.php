<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->normalizePostVersionTable();
        $this->normalizePostMediaPivotTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally no-op: this migration normalizes legacy table names.
    }

    private function normalizePostVersionTable(): void
    {
        $canonicalTable = 'publishing_post_versions';
        $legacyTable = 'publishing_post_version';

        if (! Schema::hasTable($legacyTable)) {
            return;
        }

        if (! Schema::hasTable($canonicalTable)) {
            Schema::rename($legacyTable, $canonicalTable);

            return;
        }

        DB::statement("\n            insert into {$canonicalTable} (id, post_id, content, version_number, created_at, updated_at)\n            select l.id, l.post_id, l.content, l.version_number, l.created_at, l.updated_at\n            from {$legacyTable} l\n            left join {$canonicalTable} c on c.id = l.id\n            where c.id is null\n        ");

        Schema::drop($legacyTable);
    }

    private function normalizePostMediaPivotTable(): void
    {
        $canonicalTable = 'publishing_post_media';
        $legacyTable = 'publishing__post_media';

        if (! Schema::hasTable($legacyTable)) {
            return;
        }

        if (! Schema::hasTable($canonicalTable)) {
            Schema::rename($legacyTable, $canonicalTable);

            return;
        }

        DB::statement("\n            insert into {$canonicalTable} (post_id, media_id)\n            select l.post_id, l.media_id\n            from {$legacyTable} l\n            left join {$canonicalTable} c\n                on c.post_id = l.post_id and c.media_id = l.media_id\n            where c.post_id is null\n        ");

        Schema::drop($legacyTable);
    }
};
