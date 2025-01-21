<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventCheckOutController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\IntercomController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ContactMailController;
use App\Http\Controllers\Api\StripeEventController;
use App\Http\Controllers\Api\PaymentVerificationController;
use App\Http\Controllers\Api\QrCodeVerificationController;
use App\Http\Controllers\Api\EventOrderApiController;

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
    Route::post('/checkout', [EventCheckOutController::class, 'checkout'])->name('checkout');
    Route::get('/success', [EventCheckOutController::class, 'success'])->name('checkout.success');
    Route::get('/cancel', [EventCheckOutController::class, 'cancel'])->name('checkout.cancel');
    Route::post('/webhook', [EventCheckOutController::class, 'webhook'])->name('checkout.webhook');
});

// Protected Routes
Route::middleware('authApi')->group(function () {
    // Contact & Intercom Routes
    Route::apiResource('contacts', ContactMailController::class);
    Route::post('/generate-hmac', [IntercomController::class, 'generateHmac']);

    // Event Management Routes
    Route::apiResource('events', EventApiController::class);

    // Verify Payment Route
    Route::get('/verify-payment/{sessionId}', [PaymentVerificationController::class, 'verifyPayment']);

    // Stripe Event Routes
    Route::prefix('stripe')->group(function () {

        // Event Management
        Route::get('/events', [StripeEventController::class, 'getEvents']);
        Route::get('/get-event/{id}', [StripeEventController::class, 'getEvent']);
        Route::post('/create-event', [StripeEventController::class, 'createEvent']);
        Route::put('/update-event/{id}', [StripeEventController::class, 'updateEvent']);
        Route::post('/update-image/{id}', [StripeEventController::class, 'updateImage']);
        Route::put('/archive-event/{id}', [StripeEventController::class, 'archiveEvent']);
    });

    // Order Routes
    Route::apiResource('event-orders', EventOrderApiController::class)->except(['store']);
    Route::post('/event-orders/{order}/regenerate-qr', [EventOrderApiController::class, 'regenerateQrCode']);
    Route::get('event-orders/{order}/invoice', [EventOrderApiController::class, 'generateInvoice']);

    // Route de vérification QR code
    Route::post('/verify-qrcode', [QrCodeVerificationController::class, 'verify']);
});
