<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// ─────────────────────────────────────────────────────────────────
// EVENTS de Identity
// ─────────────────────────────────────────────────────────────────
use Domains\Identity\Events\UserRegistered;
use Domains\Identity\Events\UserEmailVerified;
use Domains\Identity\Events\WorkspaceCreated;
use Domains\Identity\Events\MembershipCreated;

// ─────────────────────────────────────────────────────────────────
// LISTENERS de Activity
// ─────────────────────────────────────────────────────────────────
use Domains\Activity\Listeners\LogUserRegistered;
use Domains\Activity\Listeners\LogUserEmailVerified;
use Domains\Activity\Listeners\LogWorkspaceCreated;
use Domains\Activity\Listeners\LogMembershipCreated;

/**
 * EventServiceProvider: Configuración central de eventos
 *
 * PROPÓSITO:
 * - Conectar eventos de Identity con listeners de Activity
 * - Único punto de acoplamiento entre dominios
 * - Centralizar la configuración de eventos del sistema
 *
 * ARQUITECTURA EVENT-DRIVEN:
 * - Identity dispara eventos (no conoce listeners)
 * - Activity define listeners (conoce eventos de Identity)
 * - Este provider conecta ambos (único punto de acoplamiento)
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // ─────────────────────────────────────────────────────────
        // Identity Events → Activity Listeners
        // ─────────────────────────────────────────────────────────

        UserRegistered::class => [
            LogUserRegistered::class,
            // Activity: registrar auditoría
            // Futuro: SendWelcomeEmail::class,
            // Futuro: TrackUserSignup::class,
        ],

        UserEmailVerified::class => [
            LogUserEmailVerified::class,   // Activity: registrar verificación
            // Futuro: UnlockPremiumFeatures::class,
        ],

        WorkspaceCreated::class => [
            LogWorkspaceCreated::class,    // Activity: registrar creación
            // Futuro: InitializeWorkspaceDefaults::class,
            // Futuro: SendWorkspaceWelcome::class,
        ],

        MembershipCreated::class => [
            LogMembershipCreated::class,   // Activity: registrar membresía
            // Futuro: NotifyWorkspaceOwner::class,
            // Futuro: SendMemberWelcome::class,
        ],
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
