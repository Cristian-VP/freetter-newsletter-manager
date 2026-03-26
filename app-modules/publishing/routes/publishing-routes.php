<?php

use Domains\Publishing\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('publishing')->name('publishing.')->group(function (): void {
    Route::post('/workspaces/{workspace}/posts', [PostController::class, 'store'])
        ->name('workspaces.posts.store');
    Route::post('/posts/{post}/publish', [PostController::class, 'publish'])
        ->name('posts.publish');
});
