<?php

namespace Domains\Identity\Tests\Feature;

use Domains\Identity\Models\Invitation;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_writer_and_editor_roles_can_be_persisted(): void
    {
        $workspace = Workspace::factory()->create();

        $writerInvitation = Invitation::factory()
            ->forWorkspace($workspace)
            ->writer()
            ->create();

        $editorInvitation = Invitation::factory()
            ->forWorkspace($workspace)
            ->editor()
            ->create();

        $this->assertDatabaseHas('identity_invitations', [
            'id' => $writerInvitation->id,
            'role' => 'writer',
        ]);

        $this->assertDatabaseHas('identity_invitations', [
            'id' => $editorInvitation->id,
            'role' => 'editor',
        ]);
    }

    public function test_accepting_invitation_sets_accepted_timestamp(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        $invitation = Invitation::factory()
            ->forWorkspace($workspace)
            ->editor()
            ->create([
                'accepted_by_user_id' => null,
                'accepted_at' => null,
            ]);

        $invitation->accept($user);
        $invitation->refresh();

        $this->assertNotNull($invitation->accepted_at);
        $this->assertSame($user->id, $invitation->accepted_by_user_id);
    }
}
