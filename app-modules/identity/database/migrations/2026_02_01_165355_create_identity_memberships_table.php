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
        Schema::create('identity_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                ->constrained('identity_users')
                ->onDelete('cascade');
            $table->foreignUuid('workspace_id')
                ->constrained('identity_workspaces')
                ->onDelete('cascade');
            $table->enum('role', ['owner', 'admin', 'editor', 'viewer', 'writer']);
            $table->timestamp('joined_at');
            $table->timestamps();
            $table->unique(['user_id', 'workspace_id']);
            $table->index('workspace_id', 'idx_membership_workspace_id');
            $table->index('role', 'idx_membership_role');
            $table->index(['user_id', 'role'], 'idx_membership_user_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_memberships');
    }
};
