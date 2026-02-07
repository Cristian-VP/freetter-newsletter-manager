<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sistema de alertas para anomalías.
     *
     * Propósito: Notificar admins de eventos sospechosos
     * Disparadores: Cambios de permisos, borrados en lote, rate limit exceeded
     */
    public function up(): void
    {
        Schema::create('activity_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')
                ->constrained('identity_workspaces', 'id')
                ->cascadeOnDelete()
                ->comment('Workspace property for scoping alerts');
            $table->foreignUuid('log_id')
                ->constrained('activity_logs', 'id')
                ->cascadeOnDelete()
                ->comment('Reference to the original log entry that triggered the alert');
            $table->string('alert_type')
                ->comment('hard_delete, permission_escalation, rate_limit_exceeded');
             $table->enum('severity', ['info', 'warning', 'critical'])
                ->default('warning');
            $table->timestamps();
            $table->timestamp('resolved_at')->nullable();
            $table->index(['workspace_id', 'resolved_at','severity', 'created_at'], 'idx_activity_alerts_status');
            $table->index(['workspace_id', 'created_at'], 'idx_activity_alerts_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_alerts');
    }
};
