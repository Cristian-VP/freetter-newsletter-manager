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
        Schema::create('publishing_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Foreign keys
            $table->foreignUuid('workspace_id')
                ->constrained('identity_workspace', 'id')
                ->cascadeOnDelete()
                ->comment('Workspace to which the media belongs');
            // Media details
            $table->string('path');
            $table->enum('disk', ['local', 's3'])
                ->default('local');
            $table->string('mime_type', 255);
            $table->unsignedInteger('size_kb');
            $table->timestamps();

            // Indexes
            $table->index(
                ['workspace_id', 'created_at'],
                'idx_publishing_media_workspace_created'
            );
            $table->index(
                ['workspace_id', 'disk'],
                'idx_publishing_media_workspace_disk'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishing_media');
    }
};
