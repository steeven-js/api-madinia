<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\IntercomController;
use App\Http\Controllers\Api\ContactMailController;
use App\Http\Controllers\Api\StripeEventController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// Stripe Event Routes
Route::prefix('stripe')->group(function () {
    Route::post('/checkout', [EventController::class, 'checkout'])->name('checkout');
    Route::get('/success', [EventController::class, 'success'])->name('checkout.success');
    Route::get('/cancel', [EventController::class, 'cancel'])->name('checkout.cancel');
    Route::post('/webhook', [EventController::class, 'webhook'])->name('checkout.webhook');
});

// Protected Routes
Route::middleware('authApi')->group(function () {
    // Contact & Intercom Routes
    Route::apiResource('contacts', ContactMailController::class);
    Route::post('/generate-hmac', [IntercomController::class, 'generateHmac']);

    // Event Management Routes
    Route::apiResource('events', EventApiController::class);

    // Stripe Event Routes
    Route::prefix('stripe')->group(function () {

        // Event Management
        Route::get('/events', [StripeEventController::class, 'getEvents']);
        Route::get('/get-event/{id}', [StripeEventController::class, 'getEvent']);
        Route::post('/create-event', [StripeEventController::class, 'createEvent']);
        Route::put('/update-event/{id}', [StripeEventController::class, 'updateEvent']);
        Route::post('/update-image/{id}', [StripeEventController::class, 'updateImage']);
        Route::delete('/delete-event/{id}', [StripeEventController::class, 'deleteEvent']);
    });
});
