<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_followers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('follower_id')->constrained('identity_users')->cascadeOnDelete();
            $table->foreignUuid('followed_workspace_id')->constrained('identity_workspaces')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follower_id', 'followed_workspace_id'], 'uq_community_followers_user_workspace');
            $table->index(['followed_workspace_id', 'created_at'], 'idx_community_followers_workspace_created');
            $table->index(['follower_id', 'created_at'], 'idx_community_followers_user_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_followers');
    }
};
