<?php

namespace Domains\Publishing\Http\Controllers;

use Domains\Identity\Models\Workspace;
use Domains\Publishing\Events\PostPublished;
use Domains\Publishing\Http\Requests\StorePostRequest;
use Domains\Publishing\Models\Media;
use Domains\Publishing\Models\Post;
use Domains\Publishing\Models\PostVersion;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PostController extends Controller
{
    public function store(StorePostRequest $request, string $workspace): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $workspaceModel = Workspace::query()->findOrFail($workspace);
        $postId = $request->string('post_id')->value();
        $existingPost = $postId !== '' ? Post::query()->findOrFail($postId) : null;

        abort_unless(
            $existingPost === null || $existingPost->workspace_id === $workspaceModel->id,
            403
        );

        $post = DB::transaction(function () use ($request, $workspaceModel, $user, $existingPost): Post {
            if ($existingPost) {
                $this->syncPostContent($existingPost, $request);

                return $existingPost->fresh();
            }

            return $this->createPost($request, $workspaceModel, $user);
        });

        return response()->json([
            'data' => [
                'id' => $post->id,
                'workspace_id' => $post->workspace_id,
                'status' => $post->status,
                'slug' => $post->slug,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'published_at' => $post->published_at?->toIso8601String(),
            ],
        ], $existingPost ? 200 : 201);
    }

    public function publish(Request $request, string $post): JsonResponse
    {
        $postModel = Post::query()->findOrFail($post);
        $this->authorizePostAccess($request, $postModel);
        $deliveryChannels = $this->normalizeDeliveryChannels($request->input('delivery_channels'));
        $audience = $this->normalizeAudience($request->input('audience'));

        DB::transaction(function () use ($request, $postModel, $deliveryChannels, $audience): void {
            $this->syncPostContent($postModel, $request);

            $postModel->update([
                'status' => 'published',
                'published_at' => now(),
            ]);

            $nextVersionNumber = (int) PostVersion::query()
                ->where('post_id', $postModel->id)
                ->max('version_number') + 1;

            PostVersion::query()->create([
                'post_id' => $postModel->id,
                'content' => $postModel->content,
                'version_number' => $nextVersionNumber,
                'created_at' => now(),
            ]);

            event(new PostPublished(
                post: $postModel->fresh(),
                publishedByUserId: $request->input('published_by_user_id'),
                context: [
                    'published_via' => 'http',
                    'audience' => $audience,
                    'delivery_channels' => $deliveryChannels,
                ],
            ));
        });

        return response()->json([
            'data' => [
                'id' => $postModel->id,
                'status' => 'published',
            ],
        ]);
    }

    public function schedule(Request $request, string $post): JsonResponse
    {
        $postModel = Post::query()->findOrFail($post);
        $this->authorizePostAccess($request, $postModel);

        DB::transaction(function () use ($request, $postModel): void {
            $this->syncPostContent($postModel, $request);

            $publishedAt = $this->parsePublishedAtFromRequest($request);

            $postModel->update([
                'status' => 'scheduled',
                'published_at' => $publishedAt,
            ]);
        });

        return response()->json([
            'data' => [
                'id' => $postModel->id,
                'status' => 'scheduled',
                'published_at' => $postModel->published_at?->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Request $request, string $post): JsonResponse
    {
        $postModel = Post::query()->findOrFail($post);
        $this->authorizePostAccess($request, $postModel);

        $postModel->delete();

        return response()->json([
            'data' => [
                'id' => $post,
                'deleted' => true,
            ],
        ]);
    }

    private function createPost(StorePostRequest $request, Workspace $workspaceModel, Authenticatable $user): Post
    {
        $status = $this->resolveStatus($request);
        $content = $this->normalizeContent($request->input('content'));
        $publishedAt = $status === 'scheduled'
            ? $this->parsePublishedAtFromRequest($request)
            : ($status === 'published' ? now() : null);

        $slugBase = Str::slug($request->string('title')->value());
        $slug = $slugBase;
        $suffix = 2;

        while (Post::query()->where('workspace_id', $workspaceModel->id)->where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$suffix;
            $suffix++;
        }

        $post = Post::query()->create([
            'workspace_id' => $workspaceModel->id,
            'author_id' => (string) $user->id,
            'title' => $request->string('title')->value(),
            'slug' => $slug,
            'type' => $request->string('type')->value(),
            'status' => $status,
            'content' => $content,
            'excerpt' => $request->string('excerpt')->value() ?: '',
            'carbon_score' => 0,
            'published_at' => $publishedAt,
        ]);

        $this->storeUploadedMedia($request, $post, $workspaceModel->id);

        if ($status === 'published') {
            PostVersion::query()->create([
                'post_id' => $post->id,
                'content' => $post->content,
                'version_number' => 1,
                'created_at' => now(),
            ]);

            event(new PostPublished(
                post: $post->fresh(),
                publishedByUserId: (string) $user->id,
                context: [
                    'published_via' => 'http',
                ],
            ));
        }

        return $post;
    }

    private function syncPostContent(Post $post, Request $request): void
    {
        $payload = [];

        if ($request->has('title')) {
            $payload['title'] = $request->string('title')->value();
        }

        if ($request->has('content')) {
            $payload['content'] = $this->normalizeContent($request->input('content'));
        }

        if ($request->has('excerpt')) {
            $payload['excerpt'] = $request->string('excerpt')->value() ?: '';
        }

        if ($payload !== []) {
            $post->update($payload);
        }

        $this->storeUploadedMedia($request, $post, $post->workspace_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeContent(mixed $content): array
    {
        if (is_string($content)) {
            $decodedContent = json_decode($content, true);
            $content = is_array($decodedContent) ? $decodedContent : ['blocks' => []];
        }

        if (! is_array($content)) {
            return ['blocks' => []];
        }

        return $content;
    }

    private function parsePublishedAtFromRequest(Request $request): ?Carbon
    {
        $raw = $request->input('published_at');
        if (! $raw) {
            return null;
        }

        $timezone = $request->string('timezone')->value() ?: config('app.timezone', 'UTC');

        try {
            // Expecting format like 'YYYY-MM-DDTHH:MM' from the client
            $dt = Carbon::createFromFormat('Y-m-d\TH:i', $raw, $timezone);
        } catch (\Throwable $e) {
            // Fallback to generic parse with provided timezone
            $dt = Carbon::parse($raw, $timezone);
        }

        return $dt->setTimezone('UTC');
    }

    private function resolveStatus(StorePostRequest $request): string
    {
        $status = $request->string('status')->value();

        if ($status !== '') {
            return $status;
        }

        return $request->boolean('publish_now') ? 'published' : 'draft';
    }

    private function storeUploadedMedia(Request $request, Post $post, string $workspaceId): void
    {
        $uploadedFiles = $request->file('media', []);

        if (! is_array($uploadedFiles)) {
            return;
        }

        foreach ($uploadedFiles as $uploadedFile) {
            if (! $uploadedFile instanceof UploadedFile) {
                continue;
            }

            $storedPath = $uploadedFile->store('publishing/posts/'.$workspaceId, 'local');

            $media = Media::query()->create([
                'workspace_id' => $workspaceId,
                'path' => $storedPath,
                'disk' => 'local',
                'mime_type' => $uploadedFile->getMimeType() ?? 'application/octet-stream',
                'size_kb' => (int) ceil(($uploadedFile->getSize() ?: 0) / 1024),
            ]);

            $post->media()->attach($media->id);
        }
    }

    private function authorizePostAccess(Request $request, Post $post): void
    {
        $user = $request->user();
        abort_unless($user, 401);
    }

    /**
     * @return array<int, string>
     */
    private function normalizeDeliveryChannels(mixed $deliveryChannels): array
    {
        $defaultChannels = ['web', 'email'];

        if (! is_array($deliveryChannels)) {
            return $defaultChannels;
        }

        $normalizedChannels = array_values(array_unique(array_filter(array_map(static function (mixed $channel): ?string {
            if (! is_string($channel)) {
                return null;
            }

            $normalizedChannel = strtolower(trim($channel));

            return in_array($normalizedChannel, ['web', 'email'], true) ? $normalizedChannel : null;
        }, $deliveryChannels))));

        return $normalizedChannels !== [] ? $normalizedChannels : $defaultChannels;
    }

    private function normalizeAudience(mixed $audience): string
    {
        if (! is_string($audience)) {
            return 'subscribers';
        }

        $normalizedAudience = strtolower(trim($audience));

        return in_array($normalizedAudience, ['all', 'subscribers'], true) ? $normalizedAudience : 'subscribers';
    }
}
