<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\SeasonController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\ZoneController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);

    Route::middleware('auth.jwt')->group(function () {
        Route::apiResource('tickets', TicketController::class);
        Route::apiResource('seasons', SeasonController::class);
        Route::apiResource('zones', ZoneController::class);
        Route::apiResource('categories', CategoryController::class);
    });
});
