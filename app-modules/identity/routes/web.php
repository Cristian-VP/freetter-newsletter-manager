<?php

use Domains\Identity\Http\Controllers\InvitationController;
use Domains\Identity\Http\Controllers\MagicLinkAuthController;
use Domains\Identity\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [MagicLinkAuthController::class, 'register'])
    ->middleware('web')
    ->name('register.magic-link');

Route::post('/login', [MagicLinkAuthController::class, 'login'])
    ->middleware('web')
    ->name('login.magic-link');

Route::get('/login', static fn () => redirect()->route('landing'))
    ->middleware('web')
    ->name('login');

Route::get('/magic-links/{user}', [MagicLinkAuthController::class, 'authenticate'])
    ->middleware('web')
    ->middleware('signed')
    ->name('magic-links.authenticate');

Route::prefix('identity')->name('identity.')->group(function (): void {
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('/workspaces/{workspace}/invitations', [InvitationController::class, 'store'])
        ->name('workspaces.invitations.store');
});
