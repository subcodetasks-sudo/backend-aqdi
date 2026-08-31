<?php

use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GeneralController;
use App\Http\Controllers\Api\InstructionImageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TenantRoleController;
use App\Http\Controllers\Api\UserCouponController;
use App\Http\Controllers\Api\V2\ContentPageController as V2ContentPageController;
use App\Http\Controllers\Api\V2\ContractController as V2ContractController;
use App\Http\Controllers\Api\V2\SettingContractController as V2SettingContractController;
use App\Http\Controllers\Api\V2\SmsSettingController as V2SmsSettingController;
use App\Http\Controllers\Api\V2\MeterFeeSettingController as V2MeterFeeSettingController;
use App\Http\Controllers\Api\V2\AppStatusController as V2AppStatusController;
use App\Http\Controllers\Api\V2\UncompeleteContractController as V2UncompeleteContractController;
use App\Http\Controllers\Api\V2\CouponController as V2CouponController;
use App\Http\Controllers\Api\V2\InvoiceController as V2InvoiceController;
use App\Http\Controllers\Api\V2\RealEstateControllor as V2RealEstateControllor;
use App\Http\Controllers\Api\V2\SavedRealEstateController as V2SavedRealEstateController;
use App\Http\Controllers\Api\V2\UnitEstateController as V2UnitEstateController;
use App\Http\Middleware\ApiLocalization;
use App\Http\Middleware\CheckApi;
use App\Models\Ad;
use Illuminate\Support\Facades\Route;


Route::controller(GeneralController::class)->group(function () {
    Route::get('/cities', 'cities');
    Route::get('/regions', 'regions');
    Route::get('/instrument-types', 'instrumentTypes');
    Route::get('/contract-types', 'contractTypes');
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
    Route::get('/popup-contracts', 'popupContracts');
    Route::get('/payment-content', 'paymentContent');
    Route::get('/payment-messages', 'paymentContent');
    Route::get('/contract-periods', 'contractPeriods');
    Route::get('/settings', 'settings');
    Route::get('/cover', 'cover');
});

Route::get('/app-status', [V2AppStatusController::class, 'show']);
Route::get('/website-status', [V2AppStatusController::class, 'website']);

Route::prefix('instruction-images')->controller(InstructionImageController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{key}', 'show')->where('key', '[a-z0-9\-]+');
});

Route::prefix('content-pages')->controller(V2ContentPageController::class)->group(function () {
    Route::get('/{pageKey}', 'show')->where('pageKey', 'home|about');
});

Route::prefix('setting-contracts')->controller(V2SettingContractController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show')->whereNumber('id');
});

Route::prefix('instrument-type-settings')->controller(V2SettingContractController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show')->whereNumber('id');
});

Route::get('/sms-settings', [V2SmsSettingController::class, 'show']);
Route::get('/meter-fee-settings', [V2MeterFeeSettingController::class, 'show']);

Route::prefix('tenant-roles')->controller(TenantRoleController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::match(['put', 'patch'], '/{id}', 'update');
    Route::delete('/{id}', 'destroy');
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

    Route::controller(UserCouponController::class)->group(function () {
        Route::get('/coupons/mine', 'mine');
        Route::post('/coupons/login-notification/ack', 'acknowledge');
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
        Route::post('/doc-fee', 'docFeePreview');
        Route::post('/draft', 'setDraft')->name('draft');
    });

    Route::prefix('contract')->name('v2.contract.')->controller(V2UncompeleteContractController::class)->group(function () {
        Route::get('/check-uncompleted-contract', 'checkUncompletedContract');
        Route::post('/uncompleted-contract', 'getUncompletedContractStep');
    });

    Route::controller(V2ContractController::class)->group(function () {
        Route::get('/contracts', 'index');
        Route::get('/contracts/draft', 'drafts');
        Route::get('/contracts/draft/status/{statusId}', 'draftsByStatus')->whereNumber('statusId');
        Route::get('/contracts/status/{statusId}', 'byStatus')->whereNumber('statusId');
        Route::get('/contracts/{id}', 'show');
        Route::delete('/contracts/{id}', 'destroy');
        Route::get('/getContracts/{uuid}', 'getContracts');
        Route::get('/search/{searchTerm}', 'search');
        Route::get('/financial/{uuid}', 'financial');
        Route::get('/finance-summary/{uuid}', 'financial');
    });

    Route::prefix('invoices')->controller(V2InvoiceController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/number/{invoiceNumber}', 'showByNumber')->where('invoiceNumber', 'INV-[A-Za-z0-9\-]+');
        Route::get('/{contractId}', 'show')->whereNumber('contractId');
    });

    Route::get('/contracts/{contractId}/invoice', [V2InvoiceController::class, 'show'])
        ->whereNumber('contractId');

    foreach (['realstate', 'realState'] as $realEstatePrefix) {
        Route::prefix($realEstatePrefix)->controller(V2RealEstateControllor::class)->group(function () {
            Route::post('/step1', 'step1');
            Route::post('/step2', 'step2');
            Route::post('/step3', 'step3');
            Route::post('/update/step1', 'updateStep1');
            Route::post('/update/step2', 'updateStep2');
            Route::post('/update/step3', 'updateStep3');
        });
    }

    Route::controller(V2RealEstateControllor::class)->group(function () {
        foreach (['realstate', 'realState'] as $p) {
            Route::get("/{$p}/index", 'index');
            Route::get("/{$p}/show/{id}", 'show');
            Route::get("/{$p}/units/{id}", 'showUnits');
            Route::delete("/{$p}/delete/{id}", 'delete');
            Route::get("/{$p}/all", 'all');
        }
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

    
});

Route::post('/status/{uuid}/success', [PaymentController::class, 'updateCartByIPN'])
    ->withoutMiddleware([CheckApi::class, ApiLocalization::class, 'auth:sanctum'])
    ->name('v2.callback');

Route::post('/status/{uuid}', [PaymentController::class, 'Callback'])
    ->withoutMiddleware([CheckApi::class, ApiLocalization::class, 'auth:sanctum'])
    ->name('v2.return');

Route::get('/status/result/{uuid}', [PaymentController::class, 'result'])
    ->withoutMiddleware([CheckApi::class, ApiLocalization::class, 'auth:sanctum'])
    ->name('v2.status.result');

Route::get('/status/success/{uuid}', [PaymentController::class, 'success'])
    ->withoutMiddleware([CheckApi::class, ApiLocalization::class, 'auth:sanctum'])
    ->name('v2.status.success');

Route::get('/status/error/{uuid}', [PaymentController::class, 'error'])
    ->withoutMiddleware([CheckApi::class, ApiLocalization::class, 'auth:sanctum'])
    ->name('v2.status.error');

Route::get('/payment/{uuid}', [PaymentController::class, 'index'])
    ->withoutMiddleware([CheckApi::class, ApiLocalization::class, 'auth:sanctum'])
    ->name('v2.payment.show');

Route::get('/payment/result/{uuid}', [PaymentController::class, 'paymentResult'])
    ->withoutMiddleware([CheckApi::class, ApiLocalization::class, 'auth:sanctum'])
    ->name('v2.payment.result');

Route::get('/payment/sync/{uuid}', [PaymentController::class, 'syncFromGateway'])
    ->withoutMiddleware([CheckApi::class, ApiLocalization::class, 'auth:sanctum'])
    ->name('v2.payment.sync');

