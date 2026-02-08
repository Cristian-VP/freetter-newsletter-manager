<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Feed público de actividad de un workspace.
     *
     * Propósito: Mostrar a followers del workspace qué está pasando
     * Es un SUBCONJUNTO filtrado de activity_logs
     *
     * Relación: 1 ActivityStream : 1 ActivityLog (puede haber múltiples streams de 1 log)
     */
    public function up(): void
    {
        Schema::create('activity_streams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Foreign keys
            $table->foreignUuid('workspace_id')
                ->constrained('identity_workspaces', 'id')
                ->cascadeOnDelete()
                ->comment('Workspace event property');

            $table->foreignUuid('log_id')
                ->constrained('activity_logs', 'id')
                ->cascadeOnDelete()
                ->comment('Reference to the original log entry');

            $table->string('event_type');
            $table->enum('visibility', ['public', 'admin'])
                ->default('public')
                ->comment('Determines who can see this stream entry');
            $table->timestamps();
            $table->index(['workspace_id', 'visibility','created_at'], 'idx_activity_streams_feed');
            $table->index('log_id', 'idx_activity_streams_log_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_streams');
    }
};
