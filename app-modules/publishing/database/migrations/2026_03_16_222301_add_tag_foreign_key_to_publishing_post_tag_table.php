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
        Schema::table('publishing_post_tag', function (Blueprint $table) {
            $table->foreign('tag_id', 'publishing_post_tag_tag_id_foreign')
                ->references('id')
                ->on('publishing_tags')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishing_post_tag', function (Blueprint $table) {
            $table->dropForeign('publishing_post_tag_tag_id_foreign');
        });
    }
};
