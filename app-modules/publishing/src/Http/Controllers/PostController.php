<?php

namespace Domains\Publishing\Http\Controllers;

use Domains\Identity\Models\Workspace;
use Domains\Publishing\Events\PostPublished;
use Domains\Publishing\Http\Requests\StorePostRequest;
use Domains\Publishing\Models\Media;
use Domains\Publishing\Models\Post;
use Domains\Publishing\Models\PostVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function store(StorePostRequest $request, string $workspace): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $workspaceModel = Workspace::query()->findOrFail($workspace);
        $publishNow = $request->boolean('publish_now');
        $content = $request->input('content');

        if (is_string($content)) {
            $decodedContent = json_decode($content, true);
            $content = is_array($decodedContent) ? $decodedContent : ['blocks' => []];
        }

        if (! is_array($content)) {
            $content = ['blocks' => []];
        }

        $slugBase = Str::slug($request->string('title')->value());
        $slug = $slugBase;
        $suffix = 2;

        while (Post::query()->where('workspace_id', $workspaceModel->id)->where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$suffix;
            $suffix++;
        }

        $post = DB::transaction(function () use ($request, $workspaceModel, $slug, $publishNow, $user, $content): Post {
            $post = Post::query()->create([
                'workspace_id' => $workspaceModel->id,
                'author_id' => (string) $user->id,
                'title' => $request->string('title')->value(),
                'slug' => $slug,
                'type' => $request->string('type')->value(),
                'status' => $publishNow ? 'published' : 'draft',
                'content' => $content,
                'excerpt' => $request->string('excerpt')->value() ?: '',
                'carbon_score' => 0,
                'published_at' => $publishNow ? now() : null,
            ]);

            $uploadedFiles = $request->file('media', []);
            if (is_array($uploadedFiles)) {
                foreach ($uploadedFiles as $uploadedFile) {
                    if (! $uploadedFile instanceof UploadedFile) {
                        continue;
                    }

                    $storedPath = $uploadedFile->store('publishing/posts/'.$workspaceModel->id, 'local');

                    $media = Media::query()->create([
                        'workspace_id' => $workspaceModel->id,
                        'path' => $storedPath,
                        'disk' => 'local',
                        'mime_type' => $uploadedFile->getMimeType() ?? 'application/octet-stream',
                        'size_kb' => (int) ceil(($uploadedFile->getSize() ?: 0) / 1024),
                    ]);

                    $post->media()->attach($media->id);
                }
            }

            if ($publishNow) {
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
        });

        return response()->json([
            'data' => [
                'id' => $post->id,
                'workspace_id' => $post->workspace_id,
                'status' => $post->status,
                'slug' => $post->slug,
                'published_at' => $post->published_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function publish(Request $request, string $post): JsonResponse
    {
        $postModel = Post::query()->findOrFail($post);

        DB::transaction(function () use ($request, $postModel): void {
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
}
