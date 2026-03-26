<?php

namespace Domains\Publishing\Http\Controllers;

use Domains\Identity\Models\Workspace;
use Domains\Publishing\Events\PostPublished;
use Domains\Publishing\Http\Requests\StorePostRequest;
use Domains\Publishing\Models\Post;
use Domains\Publishing\Models\PostVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function store(StorePostRequest $request, string $workspace): JsonResponse
    {
        $workspaceModel = Workspace::query()->findOrFail($workspace);

        $slugBase = Str::slug($request->string('title')->value());
        $slug = $slugBase;
        $suffix = 2;

        while (Post::query()->where('workspace_id', $workspaceModel->id)->where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$suffix;
            $suffix++;
        }

        $post = Post::query()->create([
            'workspace_id' => $workspaceModel->id,
            'author_id' => $request->string('author_id')->value(),
            'title' => $request->string('title')->value(),
            'slug' => $slug,
            'type' => $request->string('type')->value(),
            'status' => 'draft',
            'content' => $request->input('content'),
            'excerpt' => $request->string('excerpt')->value() ?: '',
            'carbon_score' => 0,
            'published_at' => null,
        ]);

        return response()->json([
            'data' => [
                'id' => $post->id,
                'workspace_id' => $post->workspace_id,
                'status' => $post->status,
                'slug' => $post->slug,
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
