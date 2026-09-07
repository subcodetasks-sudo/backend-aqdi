<?php

use App\Http\Middleware\ApiLocalization;
use App\Http\Middleware\CheckApi;
use App\Modules\Payments\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

// Payment Status & Callbacks (without API middleware for external callbacks)
Route::withoutMiddleware([CheckApi::class, ApiLocalization::class])->group(function () {
    Route::post('/status/{uuid}/success', [PaymentController::class, 'updateCartByIPN'])->name('callback');
    Route::post('/status/{uuid}', [PaymentController::class, 'Callback'])->name('return');
    Route::get('/status/result/{uuid}', [PaymentController::class, 'result'])->name('status.result');
    Route::get('/status/success/{uuid}', [PaymentController::class, 'success'])->name('status.success');
    Route::get('/status/error/{uuid}', [PaymentController::class, 'error'])->name('status.error');
    Route::get('/payment/result/{uuid}', [PaymentController::class, 'paymentResult'])->name('payment.result');

    // Payment
    Route::get('/payment/{uuid}', [PaymentController::class, 'index'])
        ->name('payment.show');
});
