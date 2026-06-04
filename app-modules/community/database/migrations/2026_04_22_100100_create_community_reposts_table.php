<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_reposts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->foreignUuid('post_id')->constrained('publishing_posts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'post_id'], 'uq_community_reposts_user_post');
            $table->index(['post_id', 'created_at'], 'idx_community_reposts_post_created');
            $table->index(['user_id', 'created_at'], 'idx_community_reposts_user_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_reposts');
    }
};
