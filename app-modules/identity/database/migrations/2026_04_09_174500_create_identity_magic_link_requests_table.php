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
        Schema::create('identity_magic_link_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email', 255);
            $table->string('ip_address', 45)->nullable();
            $table->string('endpoint', 32);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['email', 'created_at'], 'idx_identity_magic_link_requests_email_created_at');
            $table->index(['ip_address', 'created_at'], 'idx_identity_magic_link_requests_ip_created_at');
            $table->index(['endpoint', 'created_at'], 'idx_identity_magic_link_requests_endpoint_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_magic_link_requests');
    }
};
