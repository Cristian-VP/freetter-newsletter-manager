<?php

namespace Domains\Identity\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Domains\Identity\Models\User;

/**
 * Event: Un usuario verificó su email
 *
 * PROPÓSITO:
 * - Notificar que un usuario completó la verificación de email
 * - Importante para GDPR y compliance
 *
 * DATOS:
 * - User: El usuario que verificó su email
 * - verifiedAt: Timestamp de verificación
 */

class UserEmailVerified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public User $user,
        public \DateTimeImmutable $verifiedAt
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
