<?php

use App\Http\Controllers\Api\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::get('google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('google/callback', [GoogleAuthController::class, 'callback']);

    Route::middleware('auth:sanctum')->post('/logout', [GoogleAuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->get('/me', function(Illuminate\Http\Request $request) {
    return $request->user();
});
