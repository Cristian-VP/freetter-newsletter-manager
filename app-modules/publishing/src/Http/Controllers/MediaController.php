<?php

declare(strict_types=1);

namespace Domains\Publishing\Http\Controllers;

use Domains\Identity\Models\Membership;
use Domains\Publishing\Models\Media;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $canAccessWorkspaceMedia = $canAccessWorkspaceMedia || DB::table('community_followers')
            ->where('follower_id', (string) $user->id)
            ->where('followed_workspace_id', (string) $media->workspace_id)
            ->exists();

        $pivotTable = Schema::hasTable('publishing_post_media')
            ? 'publishing_post_media'
            : (Schema::hasTable('publishing__post_media') ? 'publishing__post_media' : null);

        $isAttachedToPublishedPost = false;

        if ($pivotTable !== null) {
            $isAttachedToPublishedPost = DB::table($pivotTable)
                ->join('publishing_posts', $pivotTable.'.post_id', '=', 'publishing_posts.id')
                ->where($pivotTable.'.media_id', (string) $media->id)
                ->where('publishing_posts.status', 'published')
                ->whereNotNull('publishing_posts.published_at')
                ->exists();
        }

        abort_unless($canAccessWorkspaceMedia || $isAttachedToPublishedPost, 403);

        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk((string) $media->disk);

        abort_unless($filesystem->exists((string) $media->path), 404);

        return $filesystem->response((string) $media->path);
    }
}
