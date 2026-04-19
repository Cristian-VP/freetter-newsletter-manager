<?php

use Domains\Publishing\Http\Controllers\HomeFeedController;
use Domains\Publishing\Http\Controllers\MediaController;
use Domains\Publishing\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/home', [HomeFeedController::class, 'index'])
        ->name('home');
});

Route::prefix('publishing')->name('publishing.')->middleware(['web', 'auth'])->group(function (): void {
    Route::get('/feed', [HomeFeedController::class, 'feed'])
        ->name('feed');
    Route::get('/media/{media}', [MediaController::class, 'show'])
        ->name('media.show');
    Route::post('/workspaces/{workspace}/posts', [PostController::class, 'store'])
        ->name('workspaces.posts.store');
    Route::post('/posts/{post}/publish', [PostController::class, 'publish'])
        ->name('posts.publish');
});
