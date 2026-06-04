<?php

use Domains\Audience\Http\Controllers\ImportJobController;
use Domains\Audience\Http\Controllers\SubscriberController;
use Illuminate\Support\Facades\Route;

Route::prefix('audience')->name('audience.')->group(function (): void {
    Route::post('/workspaces/{workspace}/subscribe', [SubscriberController::class, 'subscribe'])
        ->name('workspaces.subscribe');
    Route::get('/unsubscribe/{token}', [SubscriberController::class, 'unsubscribe'])
        ->name('unsubscribe');

    Route::middleware('auth')->group(function (): void {
        Route::post('/workspaces/{workspace}/imports', [ImportJobController::class, 'store'])
            ->name('workspaces.imports.store');
    });
});
