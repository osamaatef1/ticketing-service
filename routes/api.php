<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PublicTicketController;
use App\Http\Controllers\Api\V1\SeasonController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketNoteController;
use App\Http\Controllers\Api\V1\TicketReplyController;
use App\Http\Controllers\Api\V1\TicketStatusController;
use App\Http\Controllers\Api\V1\ZoneController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);

    Route::post('/public/tickets', [PublicTicketController::class, 'store']);

    Route::middleware('auth.jwt')->group(function () {
        Route::apiResource('tickets', TicketController::class);

        Route::patch('tickets/{ticket}/status', TicketStatusController::class);

        Route::get('tickets/{ticket}/replies', [TicketReplyController::class, 'index']);
        Route::post('tickets/{ticket}/replies', [TicketReplyController::class, 'store']);

        Route::get('tickets/{ticket}/notes', [TicketNoteController::class, 'index']);
        Route::post('tickets/{ticket}/notes', [TicketNoteController::class, 'store']);

        Route::apiResource('seasons', SeasonController::class);
        Route::apiResource('zones', ZoneController::class);
        Route::apiResource('categories', CategoryController::class);
    });
});
