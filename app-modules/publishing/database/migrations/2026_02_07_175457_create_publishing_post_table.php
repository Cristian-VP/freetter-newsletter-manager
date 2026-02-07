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
        Schema::create('publishing_post', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Foreign keys
            $table->foreignUuid('workspace_id')
                ->constrained('workspace')
                ->cascadeOnDelete()
                ->comment('Workspace to which the post belongs');
            $table->foreignUuid('author_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User who created the post');
            // Post details
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->enum('type', ['newsletter', 'post']);
            $table->enum('status', ['draft', 'published', 'scheduled'])->index();
            $table->jsonb('content');
            $table->string('excerpt');
            $table->decimal('carbon_score', 8, 2)->default(0);
            $table->timestamp('published_at')
                ->nullable(false)
                ->index();
            $table->timestamps();
            // Indexes
            $table->index(
                ['workspace_id', 'created_at'],
                'idx_publishing_posts_workspace_created'
            );
            $table->index(
                ['workspace_id', 'status', 'published_at'],
                 'idx_publishing_posts_published_feed'
            );
            $table->unique(
                ['workspace_id', 'slug'],
                'uq_publishing_posts_workspace_slug'
            );
            $table->index(
                ['workspace_id', 'author_id', 'created_at'],
                'idx_publishing_posts_workspace_author'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishing_post');
    }
};
