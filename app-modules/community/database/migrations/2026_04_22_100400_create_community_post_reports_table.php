<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_post_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->foreignUuid('post_id')->constrained('publishing_posts')->cascadeOnDelete();
            $table->string('category', 80);
            $table->string('reason', 500);
            $table->string('status', 40)->default('pending');
            $table->timestamps();

            $table->index(['post_id', 'status', 'created_at'], 'idx_community_post_reports_post_status_created');
            $table->index(['user_id', 'created_at'], 'idx_community_post_reports_user_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_post_reports');
    }
};
