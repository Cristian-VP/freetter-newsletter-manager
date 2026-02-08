<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('identity_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            //Foreign keys
            $table->foreignUuid('workspace_id')
                ->constrained('identity_workspaces')
                ->onDelete('cascade');
            $table->foreignUuid('accepted_by_user_id')
                ->nullable()
                ->constrained('identity_users')
                ->onDelete('set null');
            $table->string('email', 255);
            $table->enum('role', ['admin', 'owner','editor', 'viewer','writer']);
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->index('token', 'idx_invitation_token');
            $table->index('email', 'idx_invitation_email');
            $table->index('expires_at', 'idx_invitation_expires_at');
            $table->index(['workspace_id', 'email'], 'idx_invitation_workspace_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_invitations');
    }
};
