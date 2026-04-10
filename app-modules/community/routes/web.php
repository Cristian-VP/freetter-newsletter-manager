<?php

use Domains\Community\Http\Controllers\CommentController;
use Domains\Community\Http\Controllers\FollowController;
use Domains\Community\Http\Controllers\LikeController;
use Domains\Community\Http\Controllers\ModerationController;
use Illuminate\Support\Facades\Route;

Route::prefix('community')->name('community.')->middleware('auth')->group(function (): void {
    Route::post('/comments', [CommentController::class, 'store'])
        ->name('comments.store');

    Route::patch('/comments/{comment}/moderate', [ModerationController::class, 'update'])
        ->name('comments.moderate');

    Route::post('/likes', [LikeController::class, 'store'])
        ->name('likes.store');

    Route::delete('/likes', [LikeController::class, 'destroy'])
        ->name('likes.destroy');

    Route::post('/follows', [FollowController::class, 'store'])
        ->name('follows.store');

    Route::delete('/follows', [FollowController::class, 'destroy'])
        ->name('follows.destroy');
});
