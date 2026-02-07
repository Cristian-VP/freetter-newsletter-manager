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
        Schema::create('publishing__post_media', function (Blueprint $table) {
            $table->foreignUuid('post_id')
                ->constrained('publishing_post', 'id')
                ->cascadeOnDelete()
                ->comment('Reference to the post');
            $table->foreignUuid('media_id')
                ->constrained('publishing_media', 'id')
                ->cascadeOnDelete()
                ->comment('Reference to the media');
            $table->primary(
                ['post_id', 'media_id'],
                'pk_publishing_post_media'
            );

            // Indexes
            $table->index('media_id',
            'idx_publishing_post_media_media'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishing__post_media');
    }
};
