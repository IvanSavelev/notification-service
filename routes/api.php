<?php

use App\Http\Controllers\Api\V1\BulkNotificationController;
use App\Http\Controllers\Api\V1\SubscriberNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('notifications/bulk', [BulkNotificationController::class, 'store']);
    Route::get('subscribers/{subscriberId}/notifications', [SubscriberNotificationController::class, 'index']);
});
