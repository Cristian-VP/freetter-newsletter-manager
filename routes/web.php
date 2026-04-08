<?php
declare(strict_types=1);

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Landing Page Pública
Route::get('/', function () {
    return Inertia::render('Landing');
})->name('home');

Route::middleware('auth')->group(function () {

/**
 * Example Routes for Adapting to My Project
 *
 */
    // Route::redirect('settings', 'settings/profile');

    // Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    // Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    // Route::get('settings/appearance', function () {
    //     return Inertia::render('settings/appearance');
    // })->name('appearance');
});

// require __DIR__.'/web.php';
// require __DIR__.'/web.php';
// require __DIR__.'/web.php';
// require __DIR__.'/web.php';
// require __DIR__.'/delivery-routes.php';
// require __DIR__.'/activity-routes.php';
