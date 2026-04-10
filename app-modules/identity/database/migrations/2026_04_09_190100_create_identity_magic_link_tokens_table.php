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
        Schema::create('identity_magic_link_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->char('token_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('token_hash', 'uq_identity_magic_link_tokens_token_hash');
            $table->index(['user_id', 'expires_at'], 'idx_identity_magic_link_tokens_user_expires_at');
            $table->index('consumed_at', 'idx_identity_magic_link_tokens_consumed_at');

            $table->foreign('user_id', 'fk_identity_magic_link_tokens_user_id')
                ->references('id')
                ->on('identity_users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_magic_link_tokens');
    }
};
