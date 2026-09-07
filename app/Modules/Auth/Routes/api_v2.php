<?php

use App\Modules\Auth\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/signup', 'signup');
    Route::post('/verification', 'verification');
    Route::post('/resend', 'resend');
    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/reset-password-code', 'resetPasswordCode');
    Route::post('/reset-password', 'resetPassword');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/auth/logout', 'logout');
    });
});
