<?php

namespace Domains\Identity\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Domains\Identity\Models\Membership;

/**
 * Event: Se añadió un miembro a un workspace
 *
 * PROPÓSITO:
 * - Notificar que un usuario se unió a un workspace
 * - Importante para auditoría de permisos
 *
 * DATOS:
 * - Membership: La membresía creada (contiene user_id, workspace_id, role)
 */

class MembershipCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Membership $membership
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
