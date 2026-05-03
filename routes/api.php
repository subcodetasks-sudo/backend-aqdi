<?php

use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogSubDomainController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\GeneralController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RealEstateControllor;
use App\Http\Controllers\Api\SavedRealEstateController;
use App\Http\Controllers\Api\TenantRoleController;
use App\Http\Controllers\Api\UnitEstateController;
use App\Http\Middleware\ApiLocalization;
use App\Http\Middleware\CheckApi;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| All routes are organized by feature/module for better maintainability.
|
*/

// ============================================================================
// Public Routes - General Information
// ============================================================================

 
 
 
Route::controller(GeneralController::class)->group(function () {
    Route::get('/cities', 'cities');
    Route::get('/regions', 'regions');
    Route::get('/terms-and-conditions', 'termsAndConditions');
    Route::get('/privacy', 'privacy');
    Route::get('/common-questions', 'commonQuestions');
    Route::get('/bank-accounts', 'bankAccounts');
    Route::get('/services-pricing', 'servicesPricing');
    Route::get('/paperwork', 'paperwork');
    Route::get('/real-estat-type', 'realEstatType');
    Route::get('/real-estat-usage', 'realEstatUsage');
    Route::get('/units-types', 'unitsTypes');
    Route::get('/units-usage', 'unitsUsages');
    Route::get('/payments-types', 'paymentsTypes');
    Route::get('/contract-periods', 'contractPeriods');
    Route::get('/settings', 'settings');
    Route::get('/cover', 'cover');
});

// ============================================================================
// Public Routes - Authentication
// ============================================================================


Route::prefix('auth')->name('auth.')->controller(AuthController::class)->group(function () {
    Route::post('/google/callback', 'handleGoogleCallback');
    Route::post('/login', 'login');
    Route::post('/signup', 'signup');
    Route::post('/verification', 'verification');
    Route::post('/resend', 'resend');
    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/reset-password-code', 'resetPasswordCode');
    Route::post('/reset-password', 'resetPassword');
});

// ============================================================================
// Protected Routes - Require Authentication
// ============================================================================

Route::middleware('auth:sanctum')->group(function () {

    // User Profile & Account Management
    Route::controller(AuthController::class)->group(function () {
        Route::post('/auth/logout', 'logout');
        Route::get('/profile', 'profile');
        Route::post('/profile', 'updateProfile');
        Route::post('/update/password', 'updatePassword');
        Route::post('/fcm', 'updateFCMToken');
        Route::get('/notifications', 'notifications');
        Route::post('/user/deactivate', 'deactivateUser');
    });

    // Saved Properties
    Route::controller(SavedRealEstateController::class)->group(function () {
        Route::post('/save/property', 'SavedRealEstate');
    });

    // Contracts Management
    Route::prefix('contract')->name('contract.')->controller(ContractController::class)->group(function () {
        // Contract Creation Steps
        Route::post('/start', 'contractType')->name('start');
        Route::post('/step1', 'step1');
        Route::post('/step2', 'step2');
        Route::post('/step3', 'step3');
        Route::post('/step4', 'step4');
        Route::post('/step5', 'step5');  
        Route::post('/step6', 'step6');
        
        // Uncompleted Contracts
        Route::get('/check-uncompleted-contract', 'checkUncompletedContract');
        Route::post('/uncompleted-contract', 'getUncompletedContractStep');
    });

    // Contracts Listing & Management
    Route::controller(ContractController::class)->group(function () {
        Route::get('/contracts', 'index')->name('contracts');
        Route::get('/contracts/{id}', 'show');
        Route::get('/getContracts/{uuid}', 'getContracts');
        Route::get('/search/{searchTerm}', 'search');
        Route::get('/financial/{uuid}', 'financial');
    });

    // Real Estate Management
    Route::prefix('realState')->controller(RealEstateControllor::class)->group(function () {
        // Real Estate Creation Steps
        Route::post('/step1', 'step1');
        Route::post('/step2', 'step2');
        Route::post('/step3', 'step3');
        
        // Real Estate Updates
        Route::post('/update/step1', 'updateStep1');
        Route::post('/update/step2', 'updateStep2');
        Route::post('/update/step3', 'updateStep3');
    });

    // Real Estate Operations
    Route::controller(RealEstateControllor::class)->group(function () {
        Route::get('/realState/index', 'index');
        Route::get('/realState/show/{id}', 'show');
        Route::get('/real-estates/units/{id}', 'showUnits');
        Route::delete('/realState/delete/{id}', 'delete');
        Route::get('/realState/all', 'all');
    });

    // Units Management
    Route::prefix('unit')->controller(UnitEstateController::class)->group(function () {
        Route::post('/create', 'create');
        Route::get('/index/{id}', 'index');
        Route::get('/show/{id}', 'show');
        Route::post('/update/{id}', 'update');
        Route::delete('/delete/{id}', 'delete');
        Route::get('/all/{id}', 'all');
    });

  
    Route::controller(CouponController::class)->group(function () {
        Route::post('/Coupon/{uuid}', 'Coupon');
    });
 
     

});

// ============================================================================
// Payment Routes - Special Middleware Handling
// ============================================================================

// Payment Status & Callbacks (without API middleware for external callbacks)
Route::withoutMiddleware([CheckApi::class, ApiLocalization::class])->group(function () {
    Route::post('/status/{uuid}/success', [PaymentController::class, 'updateCartByIPN'])->name('callback');
    Route::post('/status/{uuid}', [PaymentController::class, 'Callback'])->name('return');
    Route::get('/status/success/{uuid}', [PaymentController::class, 'success'])->name('status.success');
    Route::get('/status/error/{uuid}', [PaymentController::class, 'error'])->name('status.error');
    
    // Payment Details (requires authentication)
    Route::get('/payment/{uuid}', [PaymentController::class, 'index'])
        ->middleware('auth:sanctum')
        ->name('payment.show');
});
