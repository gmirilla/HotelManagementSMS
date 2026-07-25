<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FolioController;
use App\Http\Controllers\Api\V1\GuestController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\RoomTypeController;
use App\Http\Controllers\PaystackWebhookController;
use Illuminate\Support\Facades\Route;

// FR-API-004: general API traffic is throttled per-token/IP; auth endpoints
// get a much tighter limiter since they're the highest-value brute-force
// target (see AppServiceProvider::configureApiRateLimiting()).
Route::middleware(['throttle:api-auth'])->prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
});

// Deliberately outside auth:sanctum — Paystack's servers call this with no
// session or token of any kind. This file sits outside the `web` middleware
// group entirely, so it's naturally CSRF-free with no bootstrap/app.php
// changes needed. See PaystackWebhookController for how it authenticates
// the request instead (per-tenant signature verification).
Route::middleware('throttle:webhooks')->post('webhooks/paystack', PaystackWebhookController::class)->name('webhooks.paystack');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    Route::middleware('ability:rooms:read')->group(function (): void {
        Route::get('room-types', [RoomTypeController::class, 'index']);
        Route::get('room-types/{roomType}/availability', [RoomTypeController::class, 'availability']);
    });

    Route::middleware('ability:guests:read,guests:write')->group(function (): void {
        Route::get('guests', [GuestController::class, 'index']);
        Route::get('guests/{guest}', [GuestController::class, 'show']);
    });

    Route::middleware('ability:guests:write')->group(function (): void {
        Route::post('guests', [GuestController::class, 'store']);
        Route::patch('guests/{guest}', [GuestController::class, 'update']);
    });

    Route::middleware('ability:bookings:read,bookings:write')->group(function (): void {
        Route::get('reservations', [ReservationController::class, 'index']);
        Route::get('reservations/{reservation}', [ReservationController::class, 'show']);
    });

    Route::middleware('ability:bookings:write')->group(function (): void {
        Route::post('reservations', [ReservationController::class, 'store']);
        Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
    });

    Route::middleware('ability:invoices:read')->group(function (): void {
        Route::get('folios/{folio}', [FolioController::class, 'show']);
    });

    Route::middleware('ability:payments:write')->group(function (): void {
        Route::post('folios/{folio}/payments', [PaymentController::class, 'store']);
    });

    Route::middleware('ability:reports:read')->prefix('reports')->group(function (): void {
        Route::get('occupancy', [ReportController::class, 'occupancy']);
        Route::get('revenue', [ReportController::class, 'revenue']);
    });
});
