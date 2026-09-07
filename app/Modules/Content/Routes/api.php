<?php

use App\Modules\Content\Controllers\Api\GeneralController;
use App\Modules\Content\Controllers\Api\InstructionImageController;
use Illuminate\Support\Facades\Route;

Route::controller(GeneralController::class)->group(function () {
    Route::get('/terms-and-conditions', 'termsAndConditions');
    Route::get('/privacy', 'privacy');
    Route::get('/common-questions', 'commonQuestions');
    Route::get('/settings', 'settings');
    Route::get('/cover', 'cover');
});

Route::prefix('instruction-images')->controller(InstructionImageController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{key}', 'show')->where('key', '[a-z0-9\-]+');
});
