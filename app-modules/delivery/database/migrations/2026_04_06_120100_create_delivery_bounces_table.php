<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_bounces', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('identity_workspaces')->cascadeOnDelete();
            $table->foreignUuid('campaign_id')->nullable()->constrained('delivery_campaigns')->nullOnDelete();
            $table->string('email');
            $table->enum('bounce_type', ['hard', 'soft', 'complaint']);
            $table->string('code', 100)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['email', 'workspace_id'], 'idx_delivery_bounces_email_workspace');
            $table->index(['workspace_id', 'bounce_type', 'created_at'], 'idx_delivery_bounces_workspace_type_created');
            $table->unique(['workspace_id', 'campaign_id', 'email', 'bounce_type', 'code'], 'uq_delivery_bounces_idempotency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_bounces');
    }
};
