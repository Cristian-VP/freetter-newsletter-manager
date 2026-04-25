<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_muted_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->foreignUuid('muted_user_id')->constrained('identity_users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'muted_user_id'], 'uq_community_muted_users_user_target');
            $table->index(['user_id', 'created_at'], 'idx_community_muted_users_user_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_muted_users');
    }
};
