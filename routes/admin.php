<?php

use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\ContractPeriodController;
use App\Http\Controllers\Admin\ContractCommentController;
use App\Http\Controllers\Admin\ContractStatusController;
use App\Http\Controllers\Admin\ContractWhatsAppController;
use App\Http\Controllers\Admin\ContentPageController;
use App\Http\Controllers\Admin\CouponAdminController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ReceivedContractController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InstructionSectionController;
use App\Http\Controllers\Admin\FilterContract;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\HomeAdminController;
use App\Http\Controllers\Admin\LocationAnalyticsController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\AppContentOverviewController;
use App\Http\Controllers\Admin\CustomerApplicationMessageController;
use App\Http\Controllers\Admin\PageContentController;
use App\Http\Controllers\Admin\PaymentTypeController;
use App\Http\Controllers\Admin\PaperworkController;
use App\Http\Controllers\Admin\ContractPaidByEmployeeController;
use App\Http\Controllers\Admin\ContractPaymentController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RealEstateController;
use App\Http\Controllers\Admin\ReaEstatUsageController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TypeRealController;
use App\Http\Controllers\Admin\UnitRealController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Admin\UnitUsageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MessageAlertController;
use App\Http\Controllers\Admin\RefundableContractController;
use App\Http\Controllers\Admin\EmployeeDashboardAnalyticsController;
use App\Http\Controllers\Admin\UserDashboardAnalyticsController;
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
            Route::get('/employee-salary', 'employeeSalary')->name('employee-salary');
            Route::get('/employee-notes', 'employeeNotes')->name('employee-notes');
            Route::post('/{id}/salary', 'storeSalary')->whereNumber('id')->name('salary.store');
            Route::post('/{id}/note', 'storeNote')->whereNumber('id')->name('note.store');
            Route::post('/logout', 'logout')->name('logout');
            Route::post('/', 'store')->name('');
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
        Route::get('/analytics/all', 'analysis')->name('analytics.all');
        Route::get('/dashboard-analytics', 'analysis')->name('dashboard-analytics');
    });

    Route::prefix('analytics/locations')->name('analytics.locations.')->controller(LocationAnalyticsController::class)->group(function () {
        Route::get('/cities', 'cities')->name('cities');
        Route::get('/', 'index')->name('index');
    });

    Route::prefix('analytics')->name('analytics.')->controller(UserDashboardAnalyticsController::class)->group(function () {
        Route::get('/user-activity-rate', 'userActivityRate')->name('user-activity-rate');
        Route::get('/top-customers/completed-orders', 'topCustomersCompletedOrders')->name('top-customers.completed-orders');
        Route::get('/top-customers/incomplete-orders', 'topCustomersIncompleteOrders')->name('top-customers.incomplete-orders');
        Route::get('/top-customers/orders', 'topCustomersOrders')->name('top-customers.orders');
        Route::get('/top-customers/returns', 'topCustomersReturns')->name('top-customers.returns');
        Route::get('/top-customers/real-estates', 'topCustomersRealEstates')->name('top-customers.real-estates');
        Route::get('/top-customers/units', 'topCustomersUnits')->name('top-customers.units');
    });

    // Alias: analytics-clients (same handlers, clients-rich response)
    Route::prefix('analytics/clients')->name('analytics.clients.')->controller(UserDashboardAnalyticsController::class)->group(function () {
        Route::get('/completed-orders', 'topCustomersCompletedOrders')->name('completed-orders');
        Route::get('/incomplete-orders', 'topCustomersIncompleteOrders')->name('incomplete-orders');
        Route::get('/orders', 'topCustomersOrders')->name('orders');
        Route::get('/returns', 'topCustomersReturns')->name('returns');
        Route::get('/real-estates', 'topCustomersRealEstates')->name('real-estates');
        Route::get('/units', 'topCustomersUnits')->name('units');
    });

    Route::prefix('refundable-contracts')->name('refundable-contracts.')->controller(RefundableContractController::class)->middleware('auth:sanctum')->group(function () {
        Route::post('/', 'store')->name('store');
    });

    Route::prefix('analytics/refunds')->name('analytics.refunds.')->controller(RefundableContractController::class)->group(function () {
        Route::get('/contracts', 'index')->name('contracts.index');
        Route::get('/contracts/{id}', 'show')->whereNumber('id')->name('contracts.show');
        Route::post('/contracts/{id}', 'update')->whereNumber('id')->name('contracts.update');
    });

    Route::prefix('analytics/employees')->name('analytics.employees.')->controller(EmployeeDashboardAnalyticsController::class)->group(function () {
        Route::get('/most-received-orders', 'mostReceivedOrders')->name('most-received-orders');
        Route::get('/most-returns', 'mostReturns')->name('most-returns');
        Route::get('/most-documented-orders', 'mostDocumentedOrders')->name('most-documented-orders');
        Route::get('/count', 'totalCount')->name('count');
        Route::get('/most-unpaid-orders', 'mostUnpaidOrders')->name('most-unpaid-orders');
    });


        // Payments Management
    Route::prefix('payments')->name('payments.')->controller(PaymentController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
    });

    // Contract payment gateway (ClickPay) — admin equivalents of routes/api.php payment block (no auth)
    Route::prefix('payment-gateway')->name('payment-gateway.')->group(function () {
        Route::post('/status/{uuid}/success', [ContractPaymentController::class, 'updateCartByIPN'])->name('callback');
        Route::post('/status/{uuid}', [ContractPaymentController::class, 'callback'])->name('return');
        Route::get('/status/success/{uuid}', [ContractPaymentController::class, 'success'])->name('status.success');
        Route::get('/status/error/{uuid}', [ContractPaymentController::class, 'error'])->name('status.error');
        Route::get('/{uuid}/payments', [ContractPaymentController::class, 'paymentsByContract'])->name('payments');
        Route::get('/{uuid}', [ContractPaymentController::class, 'paymentUrl'])->name('show');
    });

    
    // Finance Management
    Route::prefix('finance')->name('finance.')->controller(FinanceController::class)->group(function () {
        Route::get('/expenses', 'index')->name('expenses.index');
        Route::post('/expenses', 'store')->name('expenses.store');
        Route::get('/expenses/{expense}', 'show')->name('expenses.show');
        Route::put('/expenses/{expense}', 'update')->name('expenses.update');
        Route::delete('/expenses/{expense}', 'destroy')->name('expenses.destroy');
    });



    // Employee-recorded contract payments (ClickPay link on create)
    Route::prefix('contract-paid-by-employees')->name('contract-paid-by-employees.')
        ->controller(ContractPaidByEmployeeController::class)
        ->middleware('auth:sanctum')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        });

    // Received contract: which employee received the contract (`received_contracts` table)
    Route::prefix('received-contracts')->name('received-contracts.')->controller(ReceivedContractController::class)->middleware('auth:sanctum')->group(function () {
        Route::post('/', 'store')->name('store');
        Route::patch('{contractId}', 'update')->whereNumber('contractId')->name('update');
        Route::get('{contractId}', 'show')->whereNumber('contractId')->name('show');
    });

    // Contracts draft (is_draft = true)
    Route::prefix('contracts')->name('contracts.')->controller(OrderController::class)->group(function () {
        Route::get('/draft', 'draftContracts')->name('draft');
    });

    // Orders Management
    Route::prefix('orders')->name('orders.')->controller(OrderController::class)->group(function () {
        Route::get('/', 'orders')->name('index');
        Route::get('/return', 'returnOrders')->name('return');
        Route::get('/incomplete/list', 'incomplete')->name('incomplete');
        Route::get('/complete/list', 'complete')->name('complete');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/contract-status', 'updateContractStatus')->whereNumber('id')->name('update-contract-status');
        Route::post('/{id}/return-contract-status', 'updateReturnContractAcceptance')->whereNumber('id')->middleware('auth:sanctum')->name('return-contract-status');
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
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}/block', 'block')->whereNumber('id')->name('block');
        Route::post('/{id}/delete', 'deleteUser')->whereNumber('id')->name('delete');
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
        Route::post('/{id}/inactive', 'inactive')->whereNumber('id')->name('inactive');
        Route::post('/{id}/activate', 'activate')->whereNumber('id')->name('activate');
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

    // Instruction / promotional images (صور تعليمية أو إعلانية)
    Route::prefix('instruction-sections')->name('instruction-sections.')->controller(InstructionSectionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/toggle', 'toggle')->whereNumber('id')->name('toggle');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
        Route::post('/{id}/images', 'uploadImage')->whereNumber('id')->name('images.store');
        Route::post('/{id}/images/{imageId}/delete', 'destroyImage')
            ->whereNumber(['id', 'imageId'])
            ->name('images.destroy');
    });

    // Message alerts (explanatory messages) — sections & items + alerts CRUD
    Route::prefix('message-alert-sections')->name('message-alert-sections.')->group(function () {
        Route::controller(MessageAlertSectionItemController::class)->group(function () {
            Route::get('{sectionId}/items', 'indexForSection')->whereNumber('sectionId')->name('items.index');
            Route::post('{sectionId}/items', 'storeForSection')->whereNumber('sectionId')->name('items.store');
        });
        Route::controller(MessageAlertSectionController::class)->group(function () {
            Route::get('/options/list', 'options')->name('options');
            Route::get('/{audience}/options/list', 'options')
                ->where('audience', 'client|property|employee')
                ->name('options.audience');
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->whereNumber('id')->name('show');
            Route::post('/{id}', 'update')->whereNumber('id')->name('update');
            Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
        });
    });

    Route::prefix('message-alert-section-items')->name('message-alert-section-items.')->controller(MessageAlertSectionItemController::class)->group(function () {
        Route::get('/options/list', 'options')->name('options');
        Route::get('/{audience}/options/list', 'options')
            ->where('audience', 'client|property|employee')
            ->name('options.audience');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });

    Route::prefix('message-alerts')->name('message-alerts.')->controller(MessageAlertController::class)->group(function () {
        Route::get('/types', 'types')->name('types');
        Route::get('/all', 'all')->name('all');

        Route::prefix('{audience}')->where(['audience' => 'client|property|employee'])->group(function () {
            Route::get('/create', 'create')->name('create.audience');
            Route::get('/', 'index')->name('index.audience');
            Route::post('/', 'store')->name('store.audience');
            Route::get('/{id}', 'show')->whereNumber('id')->name('show.audience');
            Route::post('/{id}', 'update')->whereNumber('id')->name('update.audience');
            Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy.audience');
        });

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

    // App settings (taxes, social, banner, terms & privacy)
    Route::prefix('settings')->name('settings.')->controller(SettingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'update')->name('update');
        Route::post('/image-banner', 'updateImageBanner')->name('image-banner.update');
        Route::post('/cover', 'updateCover')->name('cover.update');
    });

    // App content dashboard (payment methods, legal pages, customer messages)
    Route::prefix('app-content')->name('app-content.')->controller(AppContentOverviewController::class)->group(function () {
        Route::get('/overview', 'overview')->name('overview');
    });

    Route::prefix('payment-types')->name('payment-types.')->controller(PaymentTypeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });

    Route::prefix('customer-messages')->name('customer-messages.')->controller(CustomerApplicationMessageController::class)->group(function () {
        Route::get('/overview', 'overview')->name('overview');
        Route::get('/all', 'all')->name('all');
        Route::get('/create', 'createForm')->name('create');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });

    Route::prefix('content')->name('content.')->controller(PageContentController::class)->group(function () {
        Route::get('/legal-pages', 'legalPages')->name('legal-pages');
        Route::get('/terms-and-conditions', 'termsAndConditions')->name('terms.show');
        Route::post('/terms-and-conditions', 'updateTermsAndConditions')->name('terms.update');
        Route::get('/privacy', 'privacy')->name('privacy.show');
        Route::post('/privacy', 'updatePrivacy')->name('privacy.update');
    });

    Route::prefix('content-pages')->name('content-pages.')->controller(ContentPageController::class)->group(function () {
        Route::get('/{pageKey}', 'show')->name('show');
        Route::post('/{pageKey}', 'upsert')->name('upsert');
    });

    // Ads Management
    Route::prefix('ads')->name('admin-ads.')->controller(AdController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
    });
