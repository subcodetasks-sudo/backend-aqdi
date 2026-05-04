<?php

use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\ContractPeriodController;
use App\Http\Controllers\Admin\ContractCommentController;
use App\Http\Controllers\Admin\ContractStatusController;
use App\Http\Controllers\Admin\ContractWhatsAppController;
use App\Http\Controllers\Admin\CouponAdminController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ReceivedContractController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FilterContract;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\HomeAdminController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageContentController;
use App\Http\Controllers\Admin\PaperworkController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RealEstateController;
use App\Http\Controllers\Admin\ReaEstatUsageController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TypeRealController;
use App\Http\Controllers\Admin\UnitRealController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Admin\UnitUsageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MessageAlertController;
use App\Http\Controllers\Admin\MessageAlertSectionController;
use App\Http\Controllers\Admin\MessageAlertSectionItemController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| These routes are for the admin panel API endpoints.
| All routes are organized by feature/module for better maintainability.
|
*/
 
     
    Route::prefix('employees')->name('employees.')->controller(EmployeeController::class)->group(function () {
        Route::post('/login', 'login_check')->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/logout', 'logout')->name('logout');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->whereNumber('id')->name('show');
            Route::post('/{id}', 'update')->whereNumber('id')->name('update');
            Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
            Route::post('/{id}/toggle-status', 'toggleStatus')->whereNumber('id')->name('toggle-status');
            Route::post('/{id}/block', 'block')->whereNumber('id')->name('block');
            Route::post('/{id}/unblock', 'unblock')->whereNumber('id')->name('unblock');
        });
    });

    // Analytics & Dashboard
    Route::controller(HomeAdminController::class)->group(function () {
        Route::get('/analytics', 'analysis')->name('analytics');
        Route::get('/dashboard-analytics', 'analysis')->name('dashboard-analytics');
    });


        // Payments Management
    Route::prefix('payments')->name('payments.')->controller(PaymentController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
    });

    
    // Finance Management
    Route::prefix('finance')->name('finance.')->controller(FinanceController::class)->group(function () {
        Route::get('/expenses', 'index')->name('expenses.index');
        Route::post('/expenses', 'store')->name('expenses.store');
        Route::get('/expenses/{expense}', 'show')->name('expenses.show');
        Route::put('/expenses/{expense}', 'update')->name('expenses.update');
        Route::delete('/expenses/{expense}', 'destroy')->name('expenses.destroy');
    });



    // Received contract: which employee received the contract (`received_contracts` table)
    Route::prefix('received-contracts')->name('received-contracts.')->controller(ReceivedContractController::class)->middleware('auth:sanctum')->group(function () {
        Route::post('/', 'store')->name('store');
        Route::get('{contractId}', 'show')->whereNumber('contractId')->name('show');
    });

    // Orders Management
    Route::prefix('orders')->name('orders.')->controller(OrderController::class)->group(function () {
        Route::get('/', 'orders')->name('index');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}/contract-status', 'updateContractStatus')->name('update-contract-status');
        Route::get('/incomplete/list', 'incomplete')->name('incomplete');
        Route::get('/complete/list', 'complete')->name('complete');
    });

    // Contract comments (employee-authenticated)
    Route::prefix('orders/{contractId}/comments')
        ->name('orders.comments.')
        ->controller(ContractCommentController::class)
        ->middleware('auth:sanctum')
        ->group(function () {
            Route::get('/', 'index')->whereNumber('contractId')->name('index');
            Route::post('/', 'store')->whereNumber('contractId')->name('store');
            Route::post('/{commentId}', 'update')->whereNumber('contractId')->whereNumber('commentId')->name('update');
            Route::post('/{commentId}/delete', 'destroy')->whereNumber('contractId')->whereNumber('commentId')->name('destroy');
        });

    // Orders Filtering
    Route::prefix('orders')->name('orders.')->controller(FilterContract::class)->group(function () {
        Route::get('/filter', 'filter')->name('filter');
    });

    // Users Management
    Route::prefix('users')->name('users.')->controller(UserController::class)->group(function () {
        Route::get('/', 'allusers')->name('index');
        Route::get('/new', 'newcommersUser')->name('new');
        Route::get('/contracts-complete', 'usersCompleteContracts')->name('contracts-complete');
        Route::post('/{id}/block', 'Block')->name('block');
        Route::post('/{id}/delete', 'deleteUser')->name('delete');
    });

    // Regions Management
    Route::prefix('regions')->name('regions.')->controller(RegionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });

    // Cities Management
    Route::prefix('cities')->name('cities.')->controller(CityController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });

    // Real Estate Management
    Route::prefix('real-estates')->name('real-estates.')->controller(RealEstateController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
    });

    // Real Estate Types Management
    Route::prefix('real-estate-types')->name('real-estate-types.')->controller(TypeRealController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/{id}', 'update')->name('update');
        Route::post('/{id}/delete', 'destroy')->name('destroy');
    });

    // Real Estate Usages Management
    Route::prefix('real-estate-usages')->name('real-estate-usages.')->controller(ReaEstatUsageController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });

    // Unit Real Estate Management
    Route::prefix('unit-real-estates')->name('unit-real-estates.')->controller(UnitRealController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/{id}', 'update')->name('update');
        Route::post('/{id}/delete', 'destroy')->name('destroy');
    });

    // Unit Types Management
    Route::prefix('unit-types')->name('unit-types.')->controller(UnitTypeController::class)->group(function () {
        Route::get('/search', 'search')->name('search');
        Route::get('/create', 'create')->name('create');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}', 'update')->name('update');
        Route::post('/{id}/delete', 'destroy')->name('destroy');
    });

    // Unit Usages Management
    Route::prefix('unit-usages')->name('unit-usages.')->controller(UnitUsageController::class)->group(function () {
        Route::get('/create', 'create')->name('create');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}', 'update')->name('update');
        Route::post('/{id}/delete', 'destroy')->name('destroy');
    });

    // Roles Management
    Route::prefix('roles')->name('roles.')->controller(RoleController::class)->group(function () {
        Route::get('/create', 'create')->name('create');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}', 'update')->name('update');
        Route::post('/{id}/delete', 'destroy')->name('destroy');
        Route::post('/{id}/assign-permissions', 'assignPermissions')->name('assign-permissions');
    });

    // Permissions Management
    Route::prefix('permissions')->name('permissions.')->controller(PermissionController::class)->group(function () {
        Route::get('/by-section', 'bySection')->name('by-section');
        Route::get('/create', 'create')->name('create');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}', 'update')->name('update');
        Route::post('/{id}/delete', 'destroy')->name('destroy');
    });

    // Contract Statuses Management
    Route::prefix('contract-statuses')->name('contract-statuses.')->controller(ContractStatusController::class)->group(function () {
        Route::get('/active', 'active')->name('active');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}', 'update')->name('update');
        Route::post('/{id}/delete', 'destroy')->name('destroy');
    });

    // Contract Periods Management
    Route::prefix('contract-periods')->name('contract-periods.')->controller(ContractPeriodController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}', 'update')->name('update');
        Route::post('/{id}/delete', 'destroy')->name('destroy');
    });

    // Contract WhatsApp Management
    Route::prefix('contract-whatsapp')->name('contract-whatsapp.')->controller(ContractWhatsAppController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/complete', 'storeComplete')->name('store.complete');
        Route::post('/incomplete', 'storeIncomplete')->name('store.incomplete');
    });

    // Coupons Management
    Route::prefix('coupons')->name('coupons.')->controller(CouponAdminController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });

    // Paperwork Management
    Route::prefix('paperworks')->name('paperworks.')->controller(PaperworkController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });

    // FAQ Management
    Route::prefix('faqs')->name('faqs.')->controller(FaqController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });

    // Message alerts (explanatory messages) — sections & items + alerts CRUD
    Route::prefix('message-alert-sections')->name('message-alert-sections.')->group(function () {
        Route::controller(MessageAlertSectionItemController::class)->group(function () {
            Route::get('{sectionId}/items', 'indexForSection')->whereNumber('sectionId')->name('items.index');
            Route::post('{sectionId}/items', 'storeForSection')->whereNumber('sectionId')->name('items.store');
        });
        Route::controller(MessageAlertSectionController::class)->group(function () {
            Route::get('/options/list', 'options')->name('options');
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->whereNumber('id')->name('show');
            Route::post('/{id}', 'update')->whereNumber('id')->name('update');
            Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
        });
    });

    Route::prefix('message-alert-section-items')->name('message-alert-section-items.')->controller(MessageAlertSectionItemController::class)->group(function () {
        Route::get('/options/list', 'options')->name('options');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });

    Route::prefix('message-alerts')->name('message-alerts.')->controller(MessageAlertController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });



    // Blogs Management
    Route::prefix('blogs')->name('blogs.')->controller(BlogController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::post('/{id}/toggle-active', 'toggleActive')->name('toggle-active');
        Route::get('/statistics', 'statistics')->name('statistics');
    });

    // Terms & Privacy Content Management
    Route::prefix('content')->name('content.')->controller(PageContentController::class)->group(function () {
        Route::get('/terms-and-conditions', 'termsAndConditions')->name('terms.show');
        Route::post('/terms-and-conditions', 'updateTermsAndConditions')->name('terms.update');
        Route::get('/privacy', 'privacy')->name('privacy.show');
        Route::post('/privacy', 'updatePrivacy')->name('privacy.update');
    });

    // Ads Management
    Route::prefix('ads')->name('admin-ads.')->controller(AdController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });
