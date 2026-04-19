<?php

namespace Domains\Identity\Actions;

use Domains\Identity\Models\Membership;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnsureDefaultWorkspaceForUser
{
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user): void {
            User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            $hasMembership = Membership::query()
                ->where('user_id', (string) $user->id)
                ->exists();

            if ($hasMembership) {
                return;
            }

            $baseName = trim((string) $user->name) !== ''
                ? trim((string) $user->name).' Workspace'
                : 'Workspace personal';

            $slugBase = Str::slug(Str::before((string) $user->email, '@'));
            if ($slugBase === '') {
                $slugBase = 'workspace-'.Str::lower(Str::random(8));
            }

            $slug = $slugBase;
            $suffix = 2;

            while (Workspace::query()->where('slug', $slug)->exists()) {
                $slug = $slugBase.'-'.$suffix;
                $suffix++;
            }

            $workspace = Workspace::query()->create([
                'name' => $baseName,
                'slug' => $slug,
                'branding_config' => [],
                'donation_config' => [],
            ]);

            Membership::query()->create([
                'user_id' => (string) $user->id,
                'workspace_id' => (string) $workspace->id,
                'role' => 'admin',
                'joined_at' => now(),
            ]);
        });
    }
}
