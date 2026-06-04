<?php

namespace Domains\Identity\Events;

use Domains\Identity\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
        public \DateTimeInterface $verifiedAt,
        public array $context = []
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
