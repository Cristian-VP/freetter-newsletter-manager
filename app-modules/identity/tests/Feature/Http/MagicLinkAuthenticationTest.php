<?php

namespace Domains\Identity\Tests\Feature\Http;

use Domains\Identity\Models\User;
use Domains\Identity\Notifications\MagicLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class MagicLinkAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_sends_magic_link(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'email' => 'new-user@example.com',
        ]);

        $response->assertRedirect();

        $user = User::query()->where('email', 'new-user@example.com')->first();

        $this->assertNotNull($user);

        Notification::assertSentTo($user, MagicLinkNotification::class);
    }

    public function test_login_sends_magic_link_for_existing_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->post('/login', [
            'email' => 'existing@example.com',
        ]);

        $response->assertRedirect();

        Notification::assertSentTo($user, MagicLinkNotification::class);
    }

    public function test_login_validates_existing_email(): void
    {
        Notification::fake();

        $response = $this->from('/')
            ->post('/login', [
                'email' => 'missing@example.com',
            ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_signed_magic_link_authenticates_user_and_redirects_to_home(): void
    {
        $user = User::factory()->unverified()->create();
        $token = (string) Str::uuid();

        DB::table('identity_magic_link_tokens')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30),
            'consumed_at' => null,
            'created_at' => now(),
        ]);

        $magicLink = URL::temporarySignedRoute(
            'magic-links.authenticate',
            now()->addMinutes(30),
            [
                'user' => $user->getKey(),
                'token' => $token,
            ]
        );

        $response = $this->get($magicLink);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_magic_link_requires_valid_signature(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('magic-links.authenticate', ['user' => $user->getKey()]));

        $response->assertForbidden();
        $this->assertGuest();
    }

    public function test_magic_link_can_only_be_used_once(): void
    {
        $user = User::factory()->unverified()->create();
        $token = (string) Str::uuid();

        DB::table('identity_magic_link_tokens')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30),
            'consumed_at' => null,
            'created_at' => now(),
        ]);

        $magicLink = URL::temporarySignedRoute(
            'magic-links.authenticate',
            now()->addMinutes(30),
            [
                'user' => $user->getKey(),
                'token' => $token,
            ]
        );

        $firstResponse = $this->get($magicLink);

        $firstResponse->assertRedirect('/home');

        $secondResponse = $this->get($magicLink);

        $secondResponse->assertForbidden();
    }

    public function test_register_is_rate_limited_after_six_attempts_per_minute(): void
    {
        Notification::fake();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $response = $this->post('/register', [
                'email' => 'rate-limit@example.com',
            ]);

            $response->assertRedirect();
        }

        $response = $this->post('/register', [
            'email' => 'rate-limit@example.com',
        ]);

        $response->assertStatus(429);
    }

    public function test_register_is_rate_limited_by_ip_with_different_emails(): void
    {
        Notification::fake();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $response = $this->post('/register', [
                'email' => "same-ip-{$attempt}@example.com",
            ]);

            $response->assertRedirect();
        }

        $response = $this->post('/register', [
            'email' => 'same-ip-over-limit@example.com',
        ]);

        $response->assertStatus(429);
    }

    public function test_logout_invalidates_session_and_requires_new_authentication(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('landing'));
        $this->assertGuest();

        $this->get('/home')->assertRedirect(route('login'));
    }

    public function test_session_status_reports_guest_user(): void
    {
        $response = $this->getJson(route('auth.session-status'));

        $response
            ->assertOk()
            ->assertJson([
                'authenticated' => false,
                'redirect_url' => route('home'),
            ]);
    }

    public function test_session_status_reports_authenticated_user(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $response = $this->actingAs($user)->getJson(route('auth.session-status'));

        $response
            ->assertOk()
            ->assertJson([
                'authenticated' => true,
                'redirect_url' => route('home'),
            ]);
    }

    public function test_session_lifetime_is_configured_to_seven_days(): void
    {
        $this->assertSame(10080, config('session.lifetime'));
        $this->assertFalse((bool) config('session.expire_on_close'));
    }
}
