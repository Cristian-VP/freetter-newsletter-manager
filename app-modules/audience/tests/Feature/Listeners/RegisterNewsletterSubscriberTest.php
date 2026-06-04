<?php

namespace Domains\Audience\Tests\Feature\Listeners;

use Domains\Audience\Models\Subscriber;
use Domains\Community\Events\UserSubscribedToWorkspace;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterNewsletterSubscriberTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_subscriber_when_user_subscribes_via_community_follow(): void
    {
        $workspace = Workspace::factory()->create();

        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Alice Example',
            'email' => 'ALICE@EXAMPLE.COM',
        ]);

        event(new UserSubscribedToWorkspace(
            workspaceId: $workspace->id,
            userId: $user->id,
        ));

        $this->assertDatabaseHas('audience_subscribers', [
            'workspace_id' => $workspace->id,
            'email' => strtolower($user->email),
            'name' => $user->name,
            'status' => 'active',
            'consent_ip' => 'community_follow_action',
        ]);

        $subscriber = Subscriber::query()
            ->where('workspace_id', $workspace->id)
            ->where('email', strtolower($user->email))
            ->first();

        $this->assertNotNull($subscriber);
        $this->assertNotEmpty($subscriber->unsubscribe_token);
    }
}
