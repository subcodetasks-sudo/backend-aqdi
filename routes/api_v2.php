<?php

use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GeneralController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\V2\ContractController as V2ContractController;
use App\Http\Controllers\Api\V2\CouponController as V2CouponController;
use App\Http\Controllers\Api\V2\RealEstateControllor as V2RealEstateControllor;
use App\Http\Controllers\Api\V2\SavedRealEstateController as V2SavedRealEstateController;
use App\Http\Controllers\Api\V2\TenantRoleController as V2TenantRoleController;
use App\Http\Controllers\Api\V2\TenantRoleController;
use App\Http\Controllers\Api\V2\UnitEstateController as V2UnitEstateController;
use App\Http\Middleware\ApiLocalization;
use App\Http\Middleware\CheckApi;
use App\Models\Ad;
use Illuminate\Support\Facades\Route;









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

Route::prefix('auth') ->controller(AuthController::class)->group(function () {
    Route::post('/google/callback', 'handleGoogleCallback');
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
        Route::get('/profile', 'profile');
        Route::post('/profile', 'updateProfile');
        Route::post('/update/password', 'updatePassword');
        Route::post('/fcm', 'updateFCMToken');
        Route::get('/notifications', 'notifications');
        Route::post('/user/deactivate', 'deactivateUser');
    });

    Route::controller(V2SavedRealEstateController::class)->group(function () {
        Route::post('/save/property', 'SavedRealEstate');
    });

    Route::prefix('contract')->name('v2.contract.')->controller(V2ContractController::class)->group(function () {
        Route::post('/start', 'start')->name('start');
        Route::post('/step1', 'step1');
        Route::post('/step2', 'step2');
        Route::post('/step3', 'step3');
        Route::post('/step4', 'step4');
        Route::post('/step5', 'step5');
        Route::post('/step6', 'step6');
        Route::get('/check-uncompleted-contract', 'checkUncompletedContract');
        Route::post('/uncompleted-contract', 'getUncompletedContractStep');
    });

    Route::controller(V2ContractController::class)->group(function () {
        Route::get('/contracts', 'index');
        Route::get('/contracts/{id}', 'show');
        Route::get('/getContracts/{uuid}', 'getContracts');
        Route::get('/search/{searchTerm}', 'search');
        Route::get('/financial/{uuid}', 'financial');
        Route::get('/finance-summary/{uuid}', 'financial');
    });

    Route::prefix('realstate')->controller(V2RealEstateControllor::class)->group(function () {
        Route::post('/step1', 'step1');
        Route::post('/step2', 'step2');
        Route::post('/step3', 'step3');
        Route::post('/update/step1', 'updateStep1');
        Route::post('/update/step2', 'updateStep2');
        Route::post('/update/step3', 'updateStep3');
    });

    Route::controller(V2RealEstateControllor::class)->group(function () {
        Route::get('/realState/index', 'index');
        Route::get('/realState/show/{id}', 'show');
        Route::get('/real-estates/units/{id}', 'showUnits');
        Route::delete('/realState/delete/{id}', 'delete');
        Route::get('/realState/all', 'all');
    });

    Route::prefix('unit')->controller(V2UnitEstateController::class)->group(function () {
        Route::post('/create', 'create');
        Route::get('/index/{id}', 'index');
        Route::get('/show/{id}', 'show');
        Route::post('/update/{id}', 'update');
        Route::delete('/delete/{id}', 'delete');
        Route::get('/all/{id}', 'all');
    });

    Route::controller(V2CouponController::class)->group(function () {
        Route::post('/Coupon/{uuid}', 'Coupon');
    });

    Route::prefix('tenant-roles')->controller(V2TenantRoleController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::match(['put', 'patch'], '/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });
    

  Route::get('ads-images', function () {
    $ads = [
        'images' => [
            'https://aqid.subcodeco.com/website/asset/images/hero.png',
            'https://aqid.subcodeco.com/website/asset/images/hero2.png',
        ],
    ];

    return response()->json([
        'data' => $ads,
        'message' => trans('api.success')
    ]);
});
   
 Route::withoutMiddleware([CheckApi::class, ApiLocalization::class])->group(function () {
        Route::post('/status/{uuid}/success', [PaymentController::class, 'updateCartByIPN'])->name('callback');
        Route::post('/status/{uuid}', [PaymentController::class, 'Callback'])->name('return');
        Route::get('/status/success/{uuid}', [PaymentController::class, 'success'])->name('status.success');
        Route::get('/status/error/{uuid}', [PaymentController::class, 'error'])->name('status.error');
        
        // Payment Details (requires authentication)
        Route::get('/payment/{uuid}', [PaymentController::class, 'index'])
            ->withoutMiddleware('auth:sanctum')
            ->name('payment.show');
    });

});

