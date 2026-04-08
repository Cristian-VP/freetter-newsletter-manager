<?php

use Domains\Identity\Http\Controllers\InvitationController;
use Domains\Identity\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('identity')->name('identity.')->group(function (): void {
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('/workspaces/{workspace}/invitations', [InvitationController::class, 'store'])
        ->name('workspaces.invitations.store');
});
