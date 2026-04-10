<?php

use Domains\Delivery\Http\Controllers\BounceWebhookController;
use Domains\Delivery\Http\Controllers\CampaignController;
use Illuminate\Support\Facades\Route;

Route::prefix('delivery')->name('delivery.')->group(function (): void {
    Route::middleware('auth')->group(function (): void {
        Route::get('/campaigns/{workspace}', [CampaignController::class, 'index'])
            ->name('campaigns.index');

        Route::post('/campaigns/{workspace}/send', [CampaignController::class, 'send'])
            ->name('campaigns.send');
    });

    Route::post('/webhooks/bounces', [BounceWebhookController::class, 'store'])
        ->name('webhooks.bounces.store');
});
