<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     /**
     * Tabla inmutable de auditoría para todas las acciones críticas.
     *
     * Propósito: Registrar de manera append-only cada cambio en el sistema
     * Datos: ~100-1000 inserts/día típicamente
     * Retención: 10 años (política de datos)
     *
     * Patrones de Consulta:
     * 1. "¿Qué hizo el usuario X?" → WHERE user_id + ORDER BY created_at DESC
     * 2. "¿Qué pasó con la entidad Y?" → WHERE entity_type, entity_id
     * 3. "¿Qué acciones tipo Z hubo?" → WHERE action
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            //Foreign key
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('identity_users')
                ->nullOnDelete()
                ->comment('User than makes the action, null for system actions');
            $table->string('action', 100);
            $table->string('entity_type', 50);
            $table->uuid('entity_id');
            $table->jsonb('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at'], 'idx_activity_logs_user_id_created_at');
            $table->index(['entity_type', 'entity_id'], 'idx_activity_logs_entity');
            $table->index('action', 'idx_activity_logs_action');
            $table->index('created_at', 'idx_activity_logs_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
