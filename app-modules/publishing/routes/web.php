<?php

use Domains\Identity\Models\Membership;
use Domains\Publishing\Http\Controllers\HomeFeedController;
use Domains\Publishing\Http\Controllers\MediaController;
use Domains\Publishing\Http\Controllers\NewsletterIndexController;
use Domains\Publishing\Http\Controllers\PostController;
use Domains\Publishing\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/home', [HomeFeedController::class, 'index'])
        ->name('home');

    Route::get('/newsletters/resume', static fn () => Inertia::render('publishing::NewsletterResume'))
        ->name('newsletters.resume');

    Route::get('/newsletters/create', function (Request $request) {
        $user = $request->user();
        abort_unless($user, 401);

        $workspaceIds = Membership::query()
            ->where('user_id', (string) $user->id)
            ->orderBy('joined_at')
            ->pluck('workspace_id');

        $workspaceId = $workspaceIds->first();
        $newsletterId = $request->query('newsletter') ?? $request->query('post');
        $post = null;

        if (is_string($newsletterId) && $newsletterId !== '') {
            $post = Post::query()->findOrFail($newsletterId);
            abort_unless($workspaceIds->contains($post->workspace_id), 403);
            $workspaceId = $post->workspace_id;
        }

        return Inertia::render('publishing::NewsletterCreate', [
            'workspace_id' => $workspaceId,
            'post' => $post ? [
                'id' => $post->id,
                'workspace_id' => $post->workspace_id,
                'title' => $post->title,
                'status' => $post->status,
                'content' => $post->content,
                'excerpt' => $post->excerpt,
                'published_at' => $post->published_at?->toIso8601String(),
                'slug' => $post->slug,
            ] : null,
        ]);
    })->name('newsletters.create');

    Route::get('/newsletters/publishing', function (Request $request) {
        $user = $request->user();
        abort_unless($user, 401);

        $workspaceIds = Membership::query()
            ->where('user_id', (string) $user->id)
            ->orderBy('joined_at')
            ->pluck('workspace_id');

        $workspaceId = $workspaceIds->first();
        $newsletterId = $request->query('newsletter') ?? $request->query('post');
        $post = null;

        if (is_string($newsletterId) && $newsletterId !== '') {
            $post = Post::query()->findOrFail($newsletterId);
            abort_unless($workspaceIds->contains($post->workspace_id), 403);
            $workspaceId = $post->workspace_id;
        }

        return Inertia::render('publishing::NewsletterPublishing', [
            'workspace_id' => $workspaceId,
            'post' => $post ? [
                'id' => $post->id,
                'workspace_id' => $post->workspace_id,
                'title' => $post->title,
                'status' => $post->status,
                'content' => $post->content,
                'excerpt' => $post->excerpt,
                'published_at' => $post->published_at?->toIso8601String(),
                'slug' => $post->slug,
            ] : null,
        ]);
    })->name('newsletters.publishing');

    Route::get('/newsletters/preview', function (Request $request) {
        $user = $request->user();
        abort_unless($user, 401);

        $newsletterId = $request->query('newsletter') ?? $request->query('post');
        $post = null;

        if (is_string($newsletterId) && $newsletterId !== '') {
            $post = Post::query()
                ->with(['workspace:id,name,slug'])
                ->findOrFail($newsletterId);

            $workspaceIds = Membership::query()
                ->where('user_id', (string) $user->id)
                ->orderBy('joined_at')
                ->pluck('workspace_id');

            abort_unless($workspaceIds->contains($post->workspace_id), 403);
        }

        return Inertia::render('publishing::NewsletterPreview', [
            'post' => $post ? [
                'id' => $post->id,
                'workspace_id' => $post->workspace_id,
                'title' => $post->title,
                'status' => $post->status,
                'excerpt' => $post->excerpt,
                'published_at' => $post->published_at?->toIso8601String(),
                'slug' => $post->slug,
                'workspace_name' => $post->workspace?->name,
                'body_text' => $post->getExcerpt(10000),
            ] : null,
        ]);
    })->name('newsletters.preview');
});

Route::prefix('publishing')->name('publishing.')->middleware(['web', 'auth'])->group(function (): void {
    Route::get('/feed', [HomeFeedController::class, 'feed'])
        ->name('feed');
    Route::get('/newsletters', [NewsletterIndexController::class, 'index'])
        ->name('newsletters.index');
    Route::post('/media', [MediaController::class, 'store'])
        ->name('media.store');
    Route::get('/media/{media}', [MediaController::class, 'show'])
        ->name('media.show');
    Route::post('/workspaces/{workspace}/posts', [PostController::class, 'store'])
        ->name('workspaces.posts.store');
    Route::post('/posts/{post}/publish', [PostController::class, 'publish'])
        ->name('posts.publish');
    Route::post('/posts/{post}/schedule', [PostController::class, 'schedule'])
        ->name('posts.schedule');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])
        ->name('posts.destroy');
});

Route::middleware('web')->group(function (): void {
    Route::get('/{workspace}/p/{post}', function ($workspace, $post) {
        return 'Post public view placeholder';
    })->name('publishing.posts.show');

    Route::get('/{workspace}/n/{post}', function ($workspace, $post) {
        return 'Newsletter public view placeholder';
    })->name('publishing.newsletters.show');
});
