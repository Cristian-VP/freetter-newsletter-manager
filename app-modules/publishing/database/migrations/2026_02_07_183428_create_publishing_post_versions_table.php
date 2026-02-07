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
        Schema::create('publishing_post_version', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Foreign keys
            $table->foreignUuid('post_id')
                ->constrained('publishing_post')
                ->cascadeOnDelete()
                ->comment('Reference to the original post');
            $table->jsonb('content');
            $table->unsignedInteger('version_number');
            $table->unique(
                ['post_id', 'version_number'],
                'uq_publishing_versions_post_version'
            );
            $table->index(
                ['post_id', 'created_at'],
                'idx_publishing_versions_post_created'
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishing_post_version');
    }
};
