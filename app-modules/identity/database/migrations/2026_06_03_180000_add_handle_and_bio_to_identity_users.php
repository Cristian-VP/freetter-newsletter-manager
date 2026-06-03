<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_users', function (Blueprint $table) {
            $table->string('handle')->unique()->nullable()->after('name');
            $table->text('bio')->nullable()->after('handle');
        });
    }

    public function down(): void
    {
        Schema::table('identity_users', function (Blueprint $table) {
            $table->dropColumn(['handle', 'bio']);
        });
    }
};
