<?php

use App\Http\Controllers\Api\V1\SeasonController;
use App\Http\Controllers\Api\V1\ZoneController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('seasons', SeasonController::class);
    Route::apiResource('zones', ZoneController::class);
});
