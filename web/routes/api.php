<?php

use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\EntityController;
use App\Http\Controllers\Api\V1\PendingUpdateController;
use App\Http\Controllers\Api\V1\SessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('campaigns/{campaignId}/dashboard', [CampaignController::class, 'dashboard']);
    Route::get('campaigns/{campaignId}/search', [CampaignController::class, 'search']);
    Route::get('campaigns/{campaignId}/entities', [CampaignController::class, 'entities']);
    Route::get('campaigns/{campaignId}/sessions', [CampaignController::class, 'sessions']);
    Route::get('campaigns/{campaignId}/pending-updates', [CampaignController::class, 'listPendingUpdates']);
    Route::post('campaigns/{campaignId}/pending-updates', [CampaignController::class, 'propose']);

    Route::get('entities/{entityId}', [EntityController::class, 'show']);
    Route::get('sessions/{sessionId}', [SessionController::class, 'show']);

    Route::post('pending-updates/{updateId}/commit', [PendingUpdateController::class, 'commit']);
    Route::post('pending-updates/{updateId}/reject', [PendingUpdateController::class, 'reject']);
});
