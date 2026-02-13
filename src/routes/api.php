<?php

use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventInviteController;
use App\Http\Controllers\Api\EventJoinController;
use App\Http\Controllers\Api\InviteResponseController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MeInviteController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::get('google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('google/callback', [GoogleAuthController::class, 'callback']);

    Route::middleware('auth:sanctum')->post('/logout', [GoogleAuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

    // Inviter par email (owner)
    Route::post('/events/{event}/invites', [EventInviteController::class, 'store'])->name('events.invites.store');
    Route::get('/events/{event}/invites', [EventInviteController::class, 'index'])->name('events.invites.index');

    // Mes invitations (in-app)
    Route::get('/me/invites', [MeInviteController::class, 'index'])->name('me.invites');
    Route::post('/invites/{invite}/accept', [InviteResponseController::class, 'accept'])->name('invites.accept');
    Route::post('/invites/{invite}/decline', [InviteResponseController::class, 'decline'])->name('invites.decline');

    // Join via token (email externe)
    Route::post('/events/join', [EventJoinController::class, 'join'])->name('events.join');
    Route::get('/me', [MeController::class, 'show'])->name('me');
});
