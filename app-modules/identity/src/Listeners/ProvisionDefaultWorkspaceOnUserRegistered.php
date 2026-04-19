<?php

namespace Domains\Identity\Listeners;

use Domains\Identity\Actions\EnsureDefaultWorkspaceForUser;
use Domains\Identity\Events\UserRegistered;

class ProvisionDefaultWorkspaceOnUserRegistered
{
    public function __construct(private EnsureDefaultWorkspaceForUser $ensureDefaultWorkspaceForUser) {}

    public function handle(UserRegistered $event): void
    {
        $this->ensureDefaultWorkspaceForUser->execute($event->user);
    }
}
