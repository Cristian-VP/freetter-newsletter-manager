<?php

namespace Domains\Identity\Http\Controllers;

use Domains\Identity\Actions\EnsureDefaultWorkspaceForUser;
use Domains\Identity\Http\Requests\LoginMagicLinkRequest;
use Domains\Identity\Http\Requests\RegisterMagicLinkRequest;
use Domains\Identity\Models\User;
use Domains\Identity\Notifications\MagicLinkNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MagicLinkAuthController extends Controller
{
    public function __construct(private EnsureDefaultWorkspaceForUser $ensureDefaultWorkspaceForUser) {}

    public function sessionStatus(Request $request): JsonResponse
    {
        return response()->json([
            'authenticated' => Auth::guard('web')->check(),
            'redirect_url' => route('home'),
        ]);
    }

    public function register(RegisterMagicLinkRequest $request): RedirectResponse
    {
        $email = Str::lower($request->string('email')->value());

        $this->enforceMagicLinkRateLimit($request, $email, 'register');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $this->nameFromEmail($email),
                'email_verified_at' => null,
            ]
        );

        $this->sendMagicLink($user);

        return back()->with('success', 'Te hemos enviado un enlace al correo. Revisa tu bandeja de entrada.');
    }

    public function login(LoginMagicLinkRequest $request): RedirectResponse
    {
        $email = Str::lower($request->string('email')->value());

        $this->enforceMagicLinkRateLimit($request, $email, 'login');

        $user = User::query()->where('email', $email)->firstOrFail();

        $this->sendMagicLink($user);

        return back()->with('success', 'Te hemos enviado un enlace al correo. Revisa tu bandeja de entrada.');
    }

    public function authenticate(Request $request, string $user): RedirectResponse
    {
        if (! URL::hasValidSignature($request)) {
            return redirect()->route('landing')->with('error', 'El enlace de acceso es invalido o ha caducado. Solicita uno nuevo.');
        }

        $identityUser = User::query()->find($user);
        if (! $identityUser) {
            return redirect()->route('landing')->with('error', 'El enlace de acceso no es valido para esta cuenta.');
        }

        $token = $request->query('token');

        if (! is_string($token) || $token === '') {
            return redirect()->route('landing')->with('error', 'El enlace de acceso no contiene un token valido.');
        }

        $tokenHash = hash('sha256', $token);
        $magicToken = DB::table('identity_magic_link_tokens')
            ->where('user_id', $identityUser->getKey())
            ->where('token_hash', $tokenHash)
            ->first();

        if ($magicToken === null) {
            return redirect()->route('landing')->with('error', 'El enlace de acceso no es valido. Solicita uno nuevo.');
        }

        if ($magicToken->consumed_at !== null) {
            return redirect()->route('landing')->with('error', 'Este enlace ya fue utilizado. Solicita uno nuevo.');
        }

        if (now()->greaterThanOrEqualTo($magicToken->expires_at)) {
            return redirect()->route('landing')->with('error', 'Este enlace ha caducado. Solicita uno nuevo.');
        }

        $tokenWasConsumed = DB::table('identity_magic_link_tokens')
            ->where('user_id', $identityUser->getKey())
            ->where('token_hash', $tokenHash)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->update([
                'consumed_at' => now(),
            ]);

        if ($tokenWasConsumed === 0) {
            return redirect()->route('landing')->with('error', 'No fue posible validar este enlace. Solicita uno nuevo.');
        }

        if ($identityUser->email_verified_at === null) {
            $identityUser->forceFill(['email_verified_at' => now()])->save();
        }

        $this->ensureDefaultWorkspaceForUser->execute($identityUser);

        Auth::guard('web')->login($identityUser, true);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    private function sendMagicLink(User $user): void
    {
        $token = (string) Str::uuid();
        $expiresAt = now()->addMinutes(30);

        DB::table('identity_magic_link_tokens')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'consumed_at' => null,
            'created_at' => now(),
        ]);

        $magicLink = URL::temporarySignedRoute(
            'magic-links.authenticate',
            $expiresAt,
            [
                'user' => $user->getKey(),
                'token' => $token,
            ]
        );

        $user->notify(new MagicLinkNotification($magicLink));
    }

    private function nameFromEmail(string $email): string
    {
        $name = Str::before($email, '@');
        $name = Str::replace(['.', '_', '-'], ' ', $name);

        return Str::title($name);
    }

    private function enforceMagicLinkRateLimit(Request $request, string $email, string $endpoint): void
    {
        $cutoff = now()->subMinute();
        $ipAddress = $request->ip();

        $attempts = DB::table('identity_magic_link_requests')
            ->where('created_at', '>=', $cutoff)
            ->where(function ($query) use ($ipAddress, $email): void {
                $query->where('email', $email);

                if ($ipAddress !== null) {
                    $query->orWhere('ip_address', $ipAddress);
                }
            })
            ->count();

        if ($attempts >= 6) {
            abort(429, 'Too many magic link requests. Please try again in a minute.');
        }

        DB::table('identity_magic_link_requests')->insert([
            'id' => (string) Str::uuid(),
            'email' => $email,
            'ip_address' => $ipAddress,
            'endpoint' => $endpoint,
            'created_at' => now(),
        ]);
    }
}
