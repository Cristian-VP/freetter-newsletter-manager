<?php

use Domains\Publishing\Http\Controllers\HomeFeedController;
use Domains\Publishing\Http\Controllers\MediaController;
use Domains\Publishing\Http\Controllers\NewsletterIndexController;
use Domains\Publishing\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/home', [HomeFeedController::class, 'index'])
        ->name('home');

    Route::get('/newsletters/resume', static fn () => Inertia::render('publishing::NewsletterResume'))
        ->name('newsletters.resume');

    Route::get('/newsletters/create', static fn () => Inertia::render('publishing::NewsletterCreate'))
        ->name('newsletters.create');
});

Route::prefix('publishing')->name('publishing.')->middleware(['web', 'auth'])->group(function (): void {
    Route::get('/feed', [HomeFeedController::class, 'feed'])
        ->name('feed');
    Route::get('/newsletters', [NewsletterIndexController::class, 'index'])
        ->name('newsletters.index');
    Route::get('/media/{media}', [MediaController::class, 'show'])
        ->name('media.show');
    Route::post('/workspaces/{workspace}/posts', [PostController::class, 'store'])
        ->name('workspaces.posts.store');
    Route::post('/posts/{post}/publish', [PostController::class, 'publish'])
        ->name('posts.publish');
});
