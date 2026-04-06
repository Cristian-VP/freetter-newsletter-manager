<?php

namespace App\Providers;

use Domains\Activity\Listeners\LogDeliveryActivity;
use Domains\Activity\Listeners\LogImportCompleted;
use Domains\Activity\Listeners\LogImportFailed;
// ─────────────────────────────────────────────────────────────────
// EVENTS de Identity
// ─────────────────────────────────────────────────────────────────
use Domains\Activity\Listeners\LogMembershipCreated;
use Domains\Activity\Listeners\LogPostPublished;
use Domains\Activity\Listeners\LogSubscriberBounced;
use Domains\Activity\Listeners\LogSubscriberCreated;
use Domains\Activity\Listeners\LogSubscriberUnsubscribed;
use Domains\Activity\Listeners\LogUserEmailVerified;
use Domains\Activity\Listeners\LogUserRegistered;
use Domains\Activity\Listeners\LogWorkspaceCreated;
use Domains\Audience\Events\ImportCompleted;
use Domains\Audience\Events\ImportFailed;
use Domains\Audience\Events\SubscriberBounced;
use Domains\Audience\Events\SubscriberCreated;
use Domains\Audience\Events\SubscriberUnsubscribed;
use Domains\Audience\Listeners\MarkSubscriberFromDeliveryBounce;
use Domains\Delivery\Events\BounceCaptured;
use Domains\Delivery\Events\CampaignCompleted;
use Domains\Delivery\Events\CampaignCreated;
use Domains\Delivery\Events\CampaignSendingStarted;
use Domains\Delivery\Events\DeliveryBounceReceived;
use Domains\Delivery\Listeners\CreateDeliveryCampaignOnPublish;
use Domains\Identity\Events\MembershipCreated;
// ─────────────────────────────────────────────────────────────────
// LISTENERS de Activity
// ─────────────────────────────────────────────────────────────────
use Domains\Identity\Events\UserEmailVerified;
use Domains\Identity\Events\UserRegistered;
use Domains\Identity\Events\WorkspaceCreated;
use Domains\Publishing\Events\PostPublished;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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

        PostPublished::class => [
            LogPostPublished::class,
            CreateDeliveryCampaignOnPublish::class,
        ],

        DeliveryBounceReceived::class => [
            MarkSubscriberFromDeliveryBounce::class,
        ],

        CampaignCreated::class => [
            LogDeliveryActivity::class,
        ],

        CampaignSendingStarted::class => [
            LogDeliveryActivity::class,
        ],

        CampaignCompleted::class => [
            LogDeliveryActivity::class,
        ],

        BounceCaptured::class => [
            LogDeliveryActivity::class,
        ],

        SubscriberCreated::class => [
            LogSubscriberCreated::class,
        ],

        SubscriberUnsubscribed::class => [
            LogSubscriberUnsubscribed::class,
        ],

        SubscriberBounced::class => [
            LogSubscriberBounced::class,
        ],

        ImportCompleted::class => [
            LogImportCompleted::class,
        ],

        ImportFailed::class => [
            LogImportFailed::class,
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
