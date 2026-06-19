<?php

use App\Http\Controllers\CognitoAuthController;
use App\Http\Controllers\Web\AuthSessionController;
use App\Http\Controllers\Web\CampaignDashboardController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\SidebarPreferencesController;
use App\Http\Controllers\Web\SessionTimelineController;
use App\Http\Controllers\Web\WorldBrowserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::prefix('api/auth')->middleware('throttle:30,1')->group(function () {
    Route::get('/authorize', [CognitoAuthController::class, 'authorize']);
    Route::get('/cli-config', [CognitoAuthController::class, 'cliConfig']);
    Route::get('/cli/token', [CognitoAuthController::class, 'cliToken']);
    Route::post('/cli/refresh', [CognitoAuthController::class, 'cliRefresh']);
    Route::get('/token', [CognitoAuthController::class, 'token']);
    Route::post('/logout', [CognitoAuthController::class, 'logout']);

    Route::middleware('auth.cognito')->group(function () {
        Route::get('/me', [CognitoAuthController::class, 'me']);
    });
});

Route::redirect('/', '/app');

Route::prefix('app')->name('app.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/login', [AuthSessionController::class, 'showLogin'])->name('login')->middleware('guest');
    Route::get('/register', [AuthSessionController::class, 'showRegister'])->name('register')->middleware('guest');
    Route::get('/auth/redirect', [AuthSessionController::class, 'redirect'])->name('auth.redirect');
    Route::post('/logout', [AuthSessionController::class, 'logout'])->name('logout')->middleware('auth');

    Route::middleware('auth')->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/preferences/spoilers', [SidebarPreferencesController::class, 'updateSpoilers'])->name('preferences.spoilers');

        Route::prefix('campaign')->name('campaign.')->group(function () {
            Route::post('/switch', [SidebarPreferencesController::class, 'switchCampaign'])->name('switch');
            Route::get('/dashboard', CampaignDashboardController::class)->name('dashboard');
            Route::redirect('/approvals', '/app/campaign/dashboard');
            Route::get('/world', [WorldBrowserController::class, 'index'])->name('world.index');
            Route::get('/world/{entityId}', [WorldBrowserController::class, 'show'])->name('world.show');
            Route::get('/sessions', [SessionTimelineController::class, 'index'])->name('sessions.index');
            Route::get('/sessions/{sessionId}', [SessionTimelineController::class, 'show'])->name('sessions.show');
        });
    });
});
