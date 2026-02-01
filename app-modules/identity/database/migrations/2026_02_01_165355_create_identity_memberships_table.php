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
        Schema::create('identity_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('workspace_id');
            $table->enum('role', ['owner', 'admin', 'editor', 'viewer']);
            $table->timestamp('joined_at');
            $table->unique(['user_id', 'workspace_id']);
            $table->index('workspace_id');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_memberships');
    }
};
