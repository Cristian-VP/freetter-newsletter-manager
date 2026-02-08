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
 * Event: Un usuario se registró en el sistema
 *
 * PROPÓSITO:
 * - Notificar que un nuevo usuario fue creado
 * - Permite auditoría, bienvenida, analytics, etc.
 *
 * DATOS:
 * - User: El usuario creado
 * - context: Datos adicionales (IP, user agent, referrer)
 *
 * CASOS DE USO:
 * - Activity: Registrar en ActivityLog
 * - Email: Enviar email de bienvenida
 * - Analytics: Trackear conversión de registro
 * - Slack: Notificar a equipo de nuevos registros
 */

class UserRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public User $user,
        public array $context = []
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
