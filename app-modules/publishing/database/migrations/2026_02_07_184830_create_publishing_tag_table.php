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
        Schema::create('publishing_tag', function (Blueprint $table) {
             $table->uuid('id')->primary();
            // Foreign keys
            $table->foreignUuid('workspace_id')
                ->constrained('identity_workspace', 'id')
                ->cascadeOnDelete()
                ->comment('Workspace to which the tag belongs');
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(
                ['workspace_id', 'slug'],
                'uq_publishing_tags_workspace_slug'
            );
            $table->index(
                ['workspace_id', 'name'],
                'idx_publishing_tags_workspace_name'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishing_tag');
    }
};
