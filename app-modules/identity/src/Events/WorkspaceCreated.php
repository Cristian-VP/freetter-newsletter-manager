<?php

namespace Domains\Identity\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Domains\Identity\Models\Workspace;

/**
 * Event: Se creó un nuevo workspace
 *
 * PROPÓSITO:
 * - Notificar creación de workspace (newsletter/blog)
 * - Permite auditoría, inicialización, analytics
 *
 * DATOS:
 * - Workspace: El workspace creado
 * - ownerId: ID del usuario propietario
 */

class WorkspaceCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Workspace $workspace,
        public ?string $ownerId = null
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
