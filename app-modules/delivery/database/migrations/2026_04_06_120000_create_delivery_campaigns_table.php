<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_campaigns', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('identity_workspaces')->cascadeOnDelete();
            $table->foreignUuid('post_id')->constrained('publishing_posts')->cascadeOnDelete();
            $table->enum('status', ['queued', 'sending', 'sent', 'failed'])->default('queued');
            $table->jsonb('stats')->default('{}');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['workspace_id', 'status', 'created_at'], 'idx_delivery_campaigns_workspace_status_created');
            $table->index(['workspace_id', 'post_id'], 'idx_delivery_campaigns_workspace_post');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_campaigns');
    }
};
