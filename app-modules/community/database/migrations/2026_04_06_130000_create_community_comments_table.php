<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_comments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->foreignUuid('post_id')->constrained('publishing_posts')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->constrained('identity_workspaces')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->text('content');
            $table->boolean('is_hidden')->default(false);
            $table->foreignUuid('moderated_by_user_id')->nullable()->constrained('identity_users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->string('moderation_reason', 500)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['post_id', 'created_at'], 'idx_community_comments_post_created');
            $table->index(['workspace_id', 'created_at'], 'idx_community_comments_workspace_created');
            $table->index('parent_id', 'idx_community_comments_parent');
            $table->index(['post_id', 'is_hidden', 'created_at'], 'idx_community_comments_post_visible');
        });

        // Agregar la self-referencing foreign key después de que la tabla esté creada
        Schema::table('community_comments', function (Blueprint $table): void {
            $table->foreign('parent_id')
                ->references('id')
                ->on('community_comments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_comments');
    }
};
