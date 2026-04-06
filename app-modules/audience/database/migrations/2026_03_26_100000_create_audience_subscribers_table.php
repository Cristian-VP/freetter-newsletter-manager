<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audience_subscribers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('identity_workspaces')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('consent_given_at');
            $table->string('consent_ip');
            $table->uuid('unsubscribe_token')->unique();
            $table->timestamps();

            $table->unique(['workspace_id', 'email']);
            $table->index(['workspace_id', 'status']);
            $table->index('unsubscribe_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_subscribers');
    }
};
