<?php

use Domains\Community\Http\Controllers\BlockController;
use Domains\Community\Http\Controllers\BookmarkController;
use Domains\Community\Http\Controllers\CommentController;
use Domains\Community\Http\Controllers\FollowController;
use Domains\Community\Http\Controllers\LikeController;
use Domains\Community\Http\Controllers\ModerationController;
use Domains\Community\Http\Controllers\MuteController;
use Domains\Community\Http\Controllers\ReportController;
use Domains\Community\Http\Controllers\RepostController;
use Illuminate\Support\Facades\Route;

Route::prefix('community')->name('community.')->middleware(['web', 'auth'])->group(function (): void {
    Route::get('/comments', [CommentController::class, 'index'])
        ->name('comments.index');

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

    Route::post('/bookmarks', [BookmarkController::class, 'store'])
        ->name('bookmarks.store');

    Route::delete('/bookmarks', [BookmarkController::class, 'destroy'])
        ->name('bookmarks.destroy');

    Route::post('/reposts', [RepostController::class, 'store'])
        ->name('reposts.store');

    Route::post('/mutes', [MuteController::class, 'store'])
        ->name('mutes.store');

    Route::post('/blocks', [BlockController::class, 'store'])
        ->name('blocks.store');

    Route::post('/reports', [ReportController::class, 'store'])
        ->name('reports.store');
});
