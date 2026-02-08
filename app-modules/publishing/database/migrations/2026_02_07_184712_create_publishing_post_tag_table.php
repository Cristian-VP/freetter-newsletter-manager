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
        Schema::create('publishing_post_tag', function (Blueprint $table) {
            $table->foreignUuid('post_id')
                ->constrained('publishing_post', 'id')
                ->cascadeOnDelete()
                ->comment('Reference to the post');
            $table->unsignedBigInteger('tag_id')
                ->constrained('publishing_tag', 'id')
                ->cascadeOnDelete()
                ->comment('Reference to the tag');
            $table->primary(
                ['post_id', 'tag_id'],
                'pk_publishing_post_tag'
            );

            // Indexes
            $table->index('tag_id',
            'idx_publishing_post_tag_tag'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishing__post_tag');
    }
};
