<?php

use Domains\Activity\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('activity')->name('activity.')->group(function (): void {
    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
});
