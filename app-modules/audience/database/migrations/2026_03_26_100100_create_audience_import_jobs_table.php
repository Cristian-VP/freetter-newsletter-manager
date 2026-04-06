<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audience_import_jobs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('identity_workspaces')->cascadeOnDelete();
            $table->foreignUuid('created_by_user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('file_path');
            $table->jsonb('stats')->nullable();
            $table->jsonb('error_log')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'created_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_import_jobs');
    }
};
