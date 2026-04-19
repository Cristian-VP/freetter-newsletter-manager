<?php

declare(strict_types=1);

namespace Domains\Publishing\Http\Controllers;

use Domains\Identity\Models\Membership;
use Domains\Publishing\Models\Media;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function show(Request $request, Media $media): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $canAccessWorkspaceMedia = Membership::query()
            ->where('workspace_id', (string) $media->workspace_id)
            ->where('user_id', (string) $user->id)
            ->exists();

        abort_unless($canAccessWorkspaceMedia, 403);

        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk((string) $media->disk);

        abort_unless($filesystem->exists((string) $media->path), 404);

        return $filesystem->response((string) $media->path);
    }
}
