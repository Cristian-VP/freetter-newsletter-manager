<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE identity_memberships DROP CONSTRAINT IF EXISTS identity_memberships_user_id_foreign');
        DB::statement('ALTER TABLE identity_memberships DROP CONSTRAINT IF EXISTS identity_memberships_workspace_id_foreign');
        DB::statement('ALTER TABLE identity_invitations DROP CONSTRAINT IF EXISTS identity_invitations_workspace_id_foreign');
        DB::statement('ALTER TABLE identity_invitations DROP CONSTRAINT IF EXISTS identity_invitations_accepted_by_user_id_foreign');

        DB::statement('ALTER TABLE identity_memberships ADD CONSTRAINT identity_memberships_user_id_foreign FOREIGN KEY (user_id) REFERENCES identity_users (id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE identity_memberships ADD CONSTRAINT identity_memberships_workspace_id_foreign FOREIGN KEY (workspace_id) REFERENCES identity_workspaces (id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE identity_invitations ADD CONSTRAINT identity_invitations_workspace_id_foreign FOREIGN KEY (workspace_id) REFERENCES identity_workspaces (id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE identity_invitations ADD CONSTRAINT identity_invitations_accepted_by_user_id_foreign FOREIGN KEY (accepted_by_user_id) REFERENCES identity_users (id) ON DELETE SET NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE identity_memberships DROP CONSTRAINT IF EXISTS identity_memberships_user_id_foreign');
        DB::statement('ALTER TABLE identity_memberships DROP CONSTRAINT IF EXISTS identity_memberships_workspace_id_foreign');
        DB::statement('ALTER TABLE identity_invitations DROP CONSTRAINT IF EXISTS identity_invitations_workspace_id_foreign');
        DB::statement('ALTER TABLE identity_invitations DROP CONSTRAINT IF EXISTS identity_invitations_accepted_by_user_id_foreign');
    }
};
