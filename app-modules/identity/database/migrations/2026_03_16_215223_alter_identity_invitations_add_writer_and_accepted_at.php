<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('identity_invitations', 'accepted_at')) {
            Schema::table('identity_invitations', function (Blueprint $table) {
                $table->timestamp('accepted_at')->nullable();
            });
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE identity_invitations DROP CONSTRAINT IF EXISTS identity_invitations_role_check');
        DB::statement("ALTER TABLE identity_invitations ADD CONSTRAINT identity_invitations_role_check CHECK (role IN ('admin', 'editor', 'writer'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            if (Schema::hasColumn('identity_invitations', 'accepted_at')) {
                Schema::table('identity_invitations', function (Blueprint $table) {
                    $table->dropColumn('accepted_at');
                });
            }

            return;
        }

        DB::statement("UPDATE identity_invitations SET role = 'editor' WHERE role = 'writer'");
        DB::statement('ALTER TABLE identity_invitations DROP CONSTRAINT IF EXISTS identity_invitations_role_check');
        DB::statement("ALTER TABLE identity_invitations ADD CONSTRAINT identity_invitations_role_check CHECK (role IN ('admin', 'editor'))");

        if (Schema::hasColumn('identity_invitations', 'accepted_at')) {
            Schema::table('identity_invitations', function (Blueprint $table) {
                $table->dropColumn('accepted_at');
            });
        }
    }
};
