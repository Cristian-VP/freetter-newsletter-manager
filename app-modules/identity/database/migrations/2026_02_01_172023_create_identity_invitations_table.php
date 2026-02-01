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
        Schema::create('identity_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->string('email', 255);
            $table->enum('role', ['owner', 'admin', 'editor', 'viewer']);
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->uuid('accepted_by_user_id')->nullable();
            $table->index('token');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_invitations');
    }
};
