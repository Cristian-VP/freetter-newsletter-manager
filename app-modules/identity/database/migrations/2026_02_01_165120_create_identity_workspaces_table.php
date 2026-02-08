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
        Schema::create('identity_workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('slug', 63)->unique();
            $table->jsonb('branding_config');
            $table->jsonb('donation_config');
            $table->timestamps();
            $table->index('slug', 'idx_workspace_slug');
            $table->index('created_at', 'idx_workspace_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_workspaces');
    }
};
