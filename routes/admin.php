<?php

use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\AppContentOverviewController;
use App\Http\Controllers\Admin\AppStatusController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\ContentPageController;
use App\Http\Controllers\Admin\ContractCommentController;
use App\Http\Controllers\Admin\ContractPaidByEmployeeController;
use App\Http\Controllers\Admin\ContractPaymentController;
use App\Http\Controllers\Admin\ContractPeriodController;
use App\Http\Controllers\Admin\ContractStatusController;
use App\Http\Controllers\Admin\ContractUnitController;
use App\Http\Controllers\Admin\ContractWhatsAppController;
use App\Http\Controllers\Admin\CouponAdminController;
use App\Http\Controllers\Admin\CustomerApplicationMessageController;
use App\Http\Controllers\Admin\DraftContractStatusController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeDashboardAnalyticsController;
use App\Http\Controllers\Admin\EmployeeKpiController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FilterContract;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\GeneralSettingController;
use App\Http\Controllers\Admin\GoogleSeoController;
use App\Http\Controllers\Admin\HomeAdminController;
use App\Http\Controllers\Admin\InstructionSectionController;
use App\Http\Controllers\Admin\LocationAnalyticsController;
use App\Http\Controllers\Admin\MessageAlertController;
use App\Http\Controllers\Admin\MessageAlertSectionController;
use App\Http\Controllers\Admin\MessageAlertSectionItemController;
use App\Http\Controllers\Admin\MeterFeeSettingController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OperatingExpenseController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageContentController;
use App\Http\Controllers\Admin\PaperworkController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentMessageController;
use App\Http\Controllers\Admin\PaymentTypeController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PopupContractController;
use App\Http\Controllers\Admin\ReaEstatUsageController;
use App\Http\Controllers\Admin\RealEstateController;
use App\Http\Controllers\Admin\ReceivedContractController;
use App\Http\Controllers\Admin\RefundableContractController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingContractController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SeoCrawlController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\SmsSettingController;
use App\Http\Controllers\Admin\TenantRoleController;
use App\Http\Controllers\Admin\TypeRealController;
use App\Http\Controllers\Admin\UnitRealController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Admin\UnitUsageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserDashboardAnalyticsController;
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
    Route::post('/refresh-token', 'refreshToken')->name('refresh-token');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', 'profile')->name('me');
        Route::get('/profile', 'profile')->name('profile');
        Route::get('/', 'index')->middleware('permission:employees.view')->name('index');
        Route::get('/employee-salary', 'employeeSalary')->middleware('permission:employee_salaries.view')->name('employee-salary');
        Route::get('/employee-notes', 'employeeNotes')->middleware('permission:employees.view')->name('employee-notes');
        Route::post('/fcm', 'updateFcmToken')->name('fcm');
        Route::post('/{id}/salary', 'storeSalary')->whereNumber('id')->middleware('permission:employee_salaries.create')->name('salary.store');
        Route::post('/{id}/note', 'storeNote')->whereNumber('id')->middleware('permission:employees.create')->name('note.store');
        Route::post('/logout', 'logout')->name('logout');
        Route::post('/', 'store')->middleware('permission:employees.create')->name('');
        Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:employees.view')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:employees.edit')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:employees.delete')->name('destroy');
        Route::post('/{id}/toggle-status', 'toggleStatus')->whereNumber('id')->middleware('permission:employees.edit')->name('toggle-status');
        Route::post('/{id}/block', 'block')->whereNumber('id')->middleware('permission:employees.edit')->name('block');
        Route::post('/{id}/unblock', 'unblock')->whereNumber('id')->middleware('permission:employees.edit')->name('unblock');
    });
});

Route::prefix('employees')->name('employees.')->middleware('auth:sanctum')->controller(EmployeeKpiController::class)->group(function () {
    Route::get('/kpis', 'index')->middleware('permission:employee_kpis.view')->name('kpis.index');
    Route::get('/me/kpis', 'me')->name('kpis.me');
    Route::get('/{id}/kpis/details', 'details')->whereNumber('id')->middleware('permission:employee_kpis.view')->name('kpis.details');
    Route::get('/{id}/kpis', 'show')->whereNumber('id')->middleware('permission:employee_kpis.view')->name('kpis.show');
});

// Firebase notifications
Route::prefix('notifications')->name('notifications.')->controller(NotificationController::class)
    ->middleware(['auth:sanctum', 'permission:notifications.create'])
    ->group(function () {
        Route::post('/send', 'send')->name('send');
        Route::post('/user', 'sendToUser')->name('user');
        Route::post('/custom-user', 'sendToCustomUser')->name('custom-user');
        Route::post('/employee', 'sendToEmployee')->name('employee');
        Route::post('/custom-employee', 'sendToCustomEmployee')->name('custom-employee');
        Route::post('/all-users', 'sendToAllUsers')->name('all-users');
        Route::post('/all-employees', 'sendToAllEmployees')->name('all-employees');
    });

// Analytics & Dashboard
Route::controller(HomeAdminController::class)
    ->middleware(['auth:sanctum', 'permission:analytics.view'])
    ->group(function () {
    Route::get('/analytics', 'analysis')->name('analytics');
    Route::get('/analytics/all', 'analysis')->name('analytics.all');
    Route::get('/dashboard-analytics', 'analysis')->name('dashboard-analytics');
});

Route::prefix('analytics/locations')->name('analytics.locations.')
    ->controller(LocationAnalyticsController::class)
    ->middleware(['auth:sanctum', 'permission:analytics.view'])
    ->group(function () {
    Route::get('/cities', 'cities')->name('cities');
    Route::get('/', 'index')->name('index');
});

Route::prefix('analytics')->name('analytics.')
    ->controller(UserDashboardAnalyticsController::class)
    ->middleware(['auth:sanctum', 'permission:analytics.view'])
    ->group(function () {
    Route::get('/user-activity-rate', 'userActivityRate')->name('user-activity-rate');
    Route::get('/top-customers/completed-orders', 'topCustomersCompletedOrders')->name('top-customers.completed-orders');
    Route::get('/top-customers/incomplete-orders', 'topCustomersIncompleteOrders')->name('top-customers.incomplete-orders');
    Route::get('/top-customers/orders', 'topCustomersOrders')->name('top-customers.orders');
    Route::get('/top-customers/returns', 'topCustomersReturns')->name('top-customers.returns');
    Route::get('/top-customers/real-estates', 'topCustomersRealEstates')->name('top-customers.real-estates');
    Route::get('/top-customers/units', 'topCustomersUnits')->name('top-customers.units');
});

// Alias: analytics-clients (same handlers, clients-rich response)
Route::prefix('analytics/clients')->name('analytics.clients.')
    ->controller(UserDashboardAnalyticsController::class)
    ->middleware(['auth:sanctum', 'permission:analytics.view'])
    ->group(function () {
    Route::get('/completed-orders', 'topCustomersCompletedOrders')->name('completed-orders');
    Route::get('/incomplete-orders', 'topCustomersIncompleteOrders')->name('incomplete-orders');
    Route::get('/orders', 'topCustomersOrders')->name('orders');
    Route::get('/returns', 'topCustomersReturns')->name('returns');
    Route::get('/real-estates', 'topCustomersRealEstates')->name('real-estates');
    Route::get('/units', 'topCustomersUnits')->name('units');
});

Route::prefix('refundable-contracts')->name('refundable-contracts.')->controller(RefundableContractController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:returned_request.view')->name('index');
    Route::post('/', 'store')->middleware('permission:returned_request.create')->name('store');
    Route::get('/{uuid}', 'show')->middleware('permission:returned_request.view')->name('show');
    Route::match(['post', 'put', 'patch'], '/{uuid}', 'update')->middleware('permission:returned_request.edit')->name('update');
});

Route::prefix('analytics/refunds')->name('analytics.refunds.')
    ->controller(RefundableContractController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/contracts', 'index')->middleware('permission:analytics.view')->name('contracts.index');
        // Body-based confirm (avoids hosting WAF 403 on POST .../contracts/{uuid})
        Route::post('/contracts/confirm', 'confirm')->middleware('permission:returned_request.retrieve')->name('contracts.confirm');
        Route::get('/contracts/{uuid}', 'show')->middleware('permission:analytics.view')->name('contracts.show');
        Route::match(['post', 'put', 'patch'], '/contracts/{uuid}', 'update')->middleware('permission:analytics.edit')->name('contracts.update');
    });

Route::prefix('analytics/employees')->name('analytics.employees.')
    ->controller(EmployeeDashboardAnalyticsController::class)
    ->middleware(['auth:sanctum', 'permission:analytics.view'])
    ->group(function () {
    Route::get('/most-received-orders', 'mostReceivedOrders')->name('most-received-orders');
    Route::get('/most-returns', 'mostReturns')->name('most-returns');
    Route::get('/most-documented-orders', 'mostDocumentedOrders')->name('most-documented-orders');
    Route::get('/count', 'totalCount')->name('count');
    Route::get('/most-unpaid-orders', 'mostUnpaidOrders')->name('most-unpaid-orders');
});

// Payments Management
Route::prefix('payments')->name('payments.')->controller(PaymentController::class)->middleware(['auth:sanctum', 'permission:payments.view'])->group(function () {
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
Route::prefix('finance')->name('finance.')->controller(FinanceController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/expenses', 'index')->middleware('permission:finance_expenses.view')->name('expenses.index');
    Route::post('/expenses', 'store')->middleware('permission:finance_expenses.create')->name('expenses.store');
    Route::get('/expenses/{expense}', 'show')->middleware('permission:finance_expenses.view')->name('expenses.show');
    Route::put('/expenses/{expense}', 'update')->middleware('permission:finance_expenses.edit')->name('expenses.update');
    Route::delete('/expenses/{expense}', 'destroy')->middleware('permission:finance_expenses.delete')->name('expenses.destroy');
});

// Operating expenses (مصاريف تشغيلية)
Route::prefix('operating-expenses')->name('operating-expenses.')
    ->controller(OperatingExpenseController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'index')->middleware('permission:operating_expenses.view')->name('index');
        Route::post('/', 'store')->middleware('permission:operating_expenses.create')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:operating_expenses.view')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:operating_expenses.edit')->name('update');
        Route::put('/{id}', 'update')->whereNumber('id')->middleware('permission:operating_expenses.edit')->name('update.put');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:operating_expenses.delete')->name('destroy');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware('permission:operating_expenses.delete')->name('destroy.delete');
    });

// Reports page (/home/reports on the admin frontend)
Route::prefix('reports')->name('reports.')
    ->controller(ReportController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/orders', 'orders')->middleware('permission:analytics.view')->name('orders');
        Route::get('/sales', 'sales')->middleware('permission:analytics.view')->name('sales');
        Route::get('/profits', 'profits')->middleware('permission:analytics.view')->name('profits');
        Route::get('/profit-settings', 'profitSettingsShow')->middleware('permission:analytics.view')->name('profit-settings.show');
        Route::put('/profit-settings', 'profitSettingsUpdate')->middleware('permission:analytics.edit')->name('profit-settings.update');
        Route::get('/customers', 'customers')->middleware('permission:analytics.view')->name('customers');
        Route::get('/performance', 'performance')->middleware('permission:analytics.view')->name('performance');
        Route::get('/marketing', 'marketing')->middleware('permission:analytics.view')->name('marketing');
        Route::get('/marketing/utm-template', 'marketingUtmTemplate')->middleware('permission:analytics.view')->name('marketing.utm-template');
        Route::post('/marketing/spend', 'importAdSpend')->middleware('permission:analytics.create')->name('marketing.spend');
        Route::post('/marketing/sync', 'syncAdSpend')->middleware('permission:analytics.create')->name('marketing.sync');
    });

// Technical SEO crawl of aqdi.sa (dashboard + issues table)
Route::prefix('seo-crawl')->name('seo-crawl.')
    ->controller(SeoCrawlController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'show')->middleware('permission:seo_crawl.view')->name('show');
        Route::post('/', 'run')->middleware('permission:seo_crawl.create')->name('store');
        Route::post('/run', 'run')->middleware('permission:seo_crawl.create')->name('run');
        Route::post('/stop', 'stop')->middleware('permission:seo_crawl.create')->name('stop');
        Route::get('/issues', 'issues')->middleware('permission:seo_crawl.view')->name('issues');
        Route::get('/issues/{issue}', 'issue')->middleware('permission:seo_crawl.view')->name('issues.show');
    });

Route::get('/seo-google/callback', [GoogleSeoController::class, 'callback'])->name('seo-google.callback');

Route::prefix('seo-google')->name('seo-google.')
    ->controller(GoogleSeoController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/status', 'status')->middleware('permission:seo_crawl.view')->name('status');
        Route::post('/connect', 'connect')->middleware('permission:seo_crawl.create')->name('connect');
        Route::post('/disconnect', 'disconnect')->middleware('permission:seo_crawl.create')->name('disconnect');
    });

// Employee-recorded contract payments (ClickPay link on create)
Route::prefix('contract-paid-by-employees')->name('contract-paid-by-employees.')
    ->controller(ContractPaidByEmployeeController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'index')->middleware('permission:contract_payments.view')->name('index');
        Route::post('/', 'store')->middleware('permission:contract_payments.create')->name('store');
        Route::get('/{id}/payment-link', 'paymentLink')->whereNumber('id')->middleware('permission:contract_payments.view')->name('payment-link');
        Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:contract_payments.view')->name('show');
    });

// Received contract: which employee received the contract (`received_contracts` table)
Route::prefix('received-contracts')->name('received-contracts.')->controller(ReceivedContractController::class)->middleware('auth:sanctum')->group(function () {
    Route::post('/', 'store')->middleware('permission:all_requests.edit')->name('store');
    Route::patch('{contractId}', 'update')->whereNumber('contractId')->middleware('permission:all_requests.edit')->name('update');
    Route::get('{contractId}', 'show')->whereNumber('contractId')->middleware('permission:all_requests.view')->name('show');
});

// Contracts lists
Route::prefix('contracts')->name('contracts.')->controller(OrderController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/draft', 'draftContracts')->middleware('permission:incomplete_request.view')->name('draft');
    Route::get('/draft/status/{statusId}', 'draftByStatus')->whereNumber('statusId')->middleware('permission:incomplete_request.view')->name('draft-by-status');
    Route::get('/completed', 'complete')->middleware('permission:completed_request.view')->name('completed');
    Route::get('/completed-draft', 'completedAndDraft')->middleware('permission:completed_request.view')->name('completed-draft');
    Route::get('/received', 'receivedOrders')->middleware('permission:request_classification.view')->name('received');
});

// Orders Management
Route::prefix('orders')->name('orders.')->controller(OrderController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'orders')->middleware('permission:all_requests.view')->name('index');
    Route::get('/return', 'returnOrders')->middleware('permission:returned_request.view')->name('return');
    Route::get('/received', 'receivedOrders')->middleware('permission:request_classification.view')->name('received');
    Route::get('/status/{statusId}', 'byStatus')->whereNumber('statusId')->middleware('permission:request_classification.view')->name('by-status');
    Route::get('/completed', 'complete')->middleware('permission:completed_request.view')->name('completed');
    Route::get('/draft', 'draftContracts')->middleware('permission:incomplete_request.view')->name('draft');
    Route::get('/draft/status/{statusId}', 'draftByStatus')->whereNumber('statusId')->middleware('permission:incomplete_request.view')->name('draft-by-status');
    Route::get('/completed-draft', 'completedAndDraft')->middleware('permission:completed_request.view')->name('completed-draft');
    Route::get('/incomplete/list', 'incomplete')->middleware('permission:incomplete_request.view')->name('incomplete');
    Route::get('/complete/list', 'complete')->middleware('permission:completed_request.view')->name('complete');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:all_requests.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:all_requests.edit')->name('update');
    Route::post('/{id}/status', 'updateStatus')->whereNumber('id')->middleware('permission:all_requests.edit')->name('update-status');
    Route::post('/{id}/contract-status', 'updateContractStatus')->whereNumber('id')->middleware('permission:all_requests.edit')->name('update-contract-status');
    Route::post('/{id}/draft-contract-status', 'updateDraftContractStatus')->whereNumber('id')->middleware('permission:all_requests.edit')->name('update-draft-contract-status');
    Route::post('/{id}/return-contract-status', 'updateReturnContractAcceptance')->whereNumber('id')->middleware('permission:returned_request.retrieve')->name('return-contract-status');
});

// Contract comments (employee-authenticated)
Route::prefix('orders/{contractId}/comments')
    ->name('orders.comments.')
    ->controller(ContractCommentController::class)
    ->middleware(['auth:sanctum', 'permission:all_requests.edit'])
    ->group(function () {
        Route::get('/', 'index')->whereNumber('contractId')->withoutMiddleware('permission:all_requests.edit')->middleware('permission:all_requests.view')->name('index');
        Route::post('/', 'store')->whereNumber('contractId')->name('store');
        Route::post('/{commentId}', 'update')->whereNumber('contractId')->whereNumber('commentId')->name('update');
        Route::post('/{commentId}/delete', 'destroy')->whereNumber('contractId')->whereNumber('commentId')->name('destroy');
    });

// Contract units (multi-unit via contract_units)
Route::prefix('orders/{contractId}/units')
    ->name('orders.units.')
    ->controller(ContractUnitController::class)
    ->middleware(['auth:sanctum', 'permission:all_requests.edit'])
    ->group(function () {
        Route::get('/', 'index')->whereNumber('contractId')->withoutMiddleware('permission:all_requests.edit')->middleware('permission:all_requests.view')->name('index');
        Route::post('/', 'store')->whereNumber('contractId')->name('store');
        Route::post('/sync', 'sync')->whereNumber('contractId')->name('sync');
        Route::get('/{unitId}', 'show')->whereNumber('contractId')->whereNumber('unitId')->withoutMiddleware('permission:all_requests.edit')->middleware('permission:all_requests.view')->name('show');
        Route::post('/{unitId}', 'update')->whereNumber('contractId')->whereNumber('unitId')->name('update');
        Route::post('/{unitId}/delete', 'destroy')->whereNumber('contractId')->whereNumber('unitId')->name('destroy');
    });

// Orders Filtering
Route::prefix('orders')->name('orders.')->controller(FilterContract::class)->middleware(['auth:sanctum', 'permission:all_requests.view'])->group(function () {
    Route::get('/filter', 'filter')->name('filter');
});

// Users Management
Route::prefix('users')->name('users.')->controller(UserController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/export', 'export')->middleware('permission:users.view')->name('export');
    Route::get('/', 'allusers')->middleware('permission:users.view')->name('index');
    Route::get('/new', 'newcommersUser')->middleware('permission:users.view')->name('new');
    Route::get('/contracts-complete', 'usersCompleteContracts')->middleware('permission:users.view')->name('contracts-complete');
    Route::get('/{id}/properties/{propertyId}/deed', 'downloadDeed')->whereNumber('id')->whereNumber('propertyId')->middleware('permission:users.view')->name('properties.deed');
    Route::get('/{id}/properties', 'properties')->whereNumber('id')->middleware('permission:users.view')->name('properties.index');
    Route::delete('/{id}/properties/{propertyId}', 'destroyProperty')->whereNumber('id')->whereNumber('propertyId')->middleware('permission:users.delete')->name('properties.destroy');
    Route::delete('/{id}/units/{unitId}', 'destroyUnit')->whereNumber('id')->whereNumber('unitId')->middleware('permission:users.delete')->name('units.destroy');
    Route::post('/{id}/discount', 'applyDiscount')->whereNumber('id')->middleware('permission:users.edit')->name('discount');
    Route::get('/{id}/coupons', 'coupons')->whereNumber('id')->middleware('permission:users.view')->name('coupons.index');
    Route::post('/{id}/coupons', 'storeCoupon')->whereNumber('id')->middleware('permission:users.create')->name('coupons.store');
    Route::get('/{id}/coupons/{couponId}', 'showCoupon')->whereNumber('id')->whereNumber('couponId')->middleware('permission:users.view')->name('coupons.show');
    Route::post('/{id}/coupons/{couponId}/deactivate', 'deactivateCoupon')->whereNumber('id')->whereNumber('couponId')->middleware('permission:users.delete')->name('coupons.deactivate');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:users.view')->name('show');
    Route::post('/{id}/block', 'block')->whereNumber('id')->middleware('permission:users.edit')->name('block');
    Route::post('/{id}/delete', 'deleteUser')->whereNumber('id')->middleware('permission:users.delete')->name('delete');
});

// Regions Management
Route::prefix('regions')->name('regions.')->controller(RegionController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:regions.view')->name('index');
    Route::post('/', 'store')->middleware('permission:regions.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:regions.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:regions.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:regions.delete')->name('destroy');
});

// Cities Management
Route::prefix('cities')->name('cities.')->controller(CityController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:cities.view')->name('index');
    Route::post('/', 'store')->middleware('permission:cities.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:cities.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:cities.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:cities.delete')->name('destroy');
});

// Real Estate Management
Route::prefix('real-estates')->name('real-estates.')->controller(RealEstateController::class)->middleware(['auth:sanctum', 'permission:real_estates.view'])->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{id}', 'show')->name('show');
});

// Real Estate Types Management
Route::prefix('real-estate-types')->name('real-estate-types.')->controller(TypeRealController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:property_reference.view')->name('index');
    Route::post('/', 'store')->middleware('permission:property_reference.create')->name('store');
    Route::post('/{id}', 'update')->middleware('permission:property_reference.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:property_reference.delete')->name('destroy');
});

// Real Estate Usages Management
Route::prefix('real-estate-usages')->name('real-estate-usages.')->controller(ReaEstatUsageController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:property_reference.view')->name('index');
    Route::post('/', 'store')->middleware('permission:property_reference.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:property_reference.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:property_reference.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:property_reference.delete')->name('destroy');
});

// Unit Real Estate Management
Route::prefix('unit-real-estates')->name('unit-real-estates.')->controller(UnitRealController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:property_reference.view')->name('index');
    Route::post('/', 'store')->middleware('permission:property_reference.create')->name('store');
    Route::post('/{id}', 'update')->middleware('permission:property_reference.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:property_reference.delete')->name('destroy');
});

// Unit Types Management
Route::prefix('unit-types')->name('unit-types.')->controller(UnitTypeController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/search', 'search')->middleware('permission:property_reference.view')->name('search');
    Route::get('/create', 'create')->middleware('permission:property_reference.view')->name('create');
    Route::get('/', 'index')->middleware('permission:property_reference.view')->name('index');
    Route::post('/', 'store')->middleware('permission:property_reference.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:property_reference.view')->name('show');
    Route::post('/{id}', 'update')->middleware('permission:property_reference.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:property_reference.delete')->name('destroy');
});

// Unit Usages Management
Route::prefix('unit-usages')->name('unit-usages.')->controller(UnitUsageController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/create', 'create')->middleware('permission:property_reference.view')->name('create');
    Route::get('/', 'index')->middleware('permission:property_reference.view')->name('index');
    Route::post('/', 'store')->middleware('permission:property_reference.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:property_reference.view')->name('show');
    Route::post('/{id}', 'update')->middleware('permission:property_reference.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:property_reference.delete')->name('destroy');
});

// Tenant roles (صفات المستأجر)
Route::prefix('tenant-roles')->name('tenant-roles.')->controller(TenantRoleController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:tenant_roles.view')->name('index');
    Route::post('/', 'store')->middleware('permission:tenant_roles.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:tenant_roles.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:tenant_roles.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:tenant_roles.delete')->name('destroy');
});

// Roles Management
Route::prefix('roles')->name('roles.')->controller(RoleController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/create', 'create')->middleware('permission:roles.view')->name('create');
    Route::get('/', 'index')->middleware('permission:roles.view')->name('index');
    Route::post('/', 'store')->middleware('permission:roles.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:roles.view')->name('show');
    Route::post('/{id}', 'update')->middleware('permission:roles.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:roles.delete')->name('destroy');
    Route::post('/{id}/assign-permissions', 'assignPermissions')->middleware('permission:roles.edit')->name('assign-permissions');
});

// Permissions Management
Route::prefix('permissions')->name('permissions.')->controller(PermissionController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/by-section', 'bySection')->middleware('permission:permissions.view')->name('by-section');
    Route::get('/create', 'create')->middleware('permission:permissions.view')->name('create');
    Route::get('/', 'index')->middleware('permission:permissions.view')->name('index');
    Route::post('/', 'store')->middleware('permission:permissions.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:permissions.view')->name('show');
    Route::post('/{id}', 'update')->middleware('permission:permissions.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:permissions.delete')->name('destroy');
});

// Contract Statuses Management
Route::prefix('contract-statuses')->name('contract-statuses.')->controller(ContractStatusController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/active', 'active')->middleware('permission:contract_statuses.view')->name('active');
    Route::get('/', 'index')->middleware('permission:contract_statuses.view')->name('index');
    Route::post('/', 'store')->middleware('permission:contract_statuses.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:contract_statuses.view')->name('show');
    Route::post('/{id}', 'update')->middleware('permission:contract_statuses.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:contract_statuses.delete')->name('destroy');
});

// Draft Contract Statuses (مسودات) — same shape as contract-statuses
Route::prefix('draft-contract-statuses')->name('draft-contract-statuses.')->controller(DraftContractStatusController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/active', 'active')->middleware('permission:draft_contract_statuses.view')->name('active');
    Route::post('/sync', 'syncFromContractStatuses')->middleware('permission:draft_contract_statuses.edit')->name('sync');
    Route::get('/', 'index')->middleware('permission:draft_contract_statuses.view')->name('index');
    Route::post('/', 'store')->middleware('permission:draft_contract_statuses.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:draft_contract_statuses.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:draft_contract_statuses.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:draft_contract_statuses.delete')->name('destroy');
});

// Contract Periods Management
Route::prefix('contract-periods')->name('contract-periods.')->controller(ContractPeriodController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:contract_periods.view')->name('index');
    Route::post('/create', 'create')->middleware('permission:contract_periods.create')->name('create');
    Route::post('/', 'store')->middleware('permission:contract_periods.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:contract_periods.view')->name('show');
    Route::post('/{id}', 'update')->middleware('permission:contract_periods.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->middleware('permission:contract_periods.delete')->name('destroy');
});

// Contract WhatsApp Management
Route::prefix('contract-whatsapp')->name('contract-whatsapp.')->controller(ContractWhatsAppController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:contract_whatsapp.view')->name('index');
    Route::post('/complete', 'storeComplete')->middleware('permission:completed_whatsapp_request.create')->name('store.complete');
    Route::post('/incomplete', 'storeIncomplete')->middleware('permission:incomplete_whatsapp_request.create')->name('store.incomplete');
});

// Coupons Management
Route::prefix('coupons')->name('coupons.')->controller(CouponAdminController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:coupons.view')->name('index');
    Route::post('/', 'store')->middleware('permission:coupons.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:coupons.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:coupons.edit')->name('update');
    Route::post('/{id}/inactive', 'inactive')->whereNumber('id')->middleware('permission:coupons.edit')->name('inactive');
    Route::post('/{id}/activate', 'activate')->whereNumber('id')->middleware('permission:coupons.edit')->name('activate');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:coupons.delete')->name('destroy');
});

// Paperwork Management
Route::prefix('paperworks')->name('paperworks.')->controller(PaperworkController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:paperworks.view')->name('index');
    Route::post('/', 'store')->middleware('permission:paperworks.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:paperworks.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:paperworks.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:paperworks.delete')->name('destroy');
});

// Popup Contract Management
Route::prefix('popup-contracts')->name('popup-contracts.')->controller(PopupContractController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:popup_contracts.view')->name('index');
    Route::post('/', 'store')->middleware('permission:popup_contracts.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:popup_contracts.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:popup_contracts.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:popup_contracts.delete')->name('destroy');
});

// Payment success / failed messages
Route::prefix('payment-messages')->name('payment-messages.')->controller(PaymentMessageController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:payment_messages.view')->name('index');
    Route::post('/', 'store')->middleware('permission:payment_messages.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:payment_messages.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:payment_messages.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:payment_messages.delete')->name('destroy');
});

// Contract settings per instrument type (SMS + buttons)
Route::prefix('setting-contracts')->name('setting-contracts.')->controller(SettingContractController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:instrument_settings.view')->name('index');
    Route::post('/', 'store')->middleware('permission:instrument_settings.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:instrument_settings.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:instrument_settings.edit')->name('update');
});

// Alias used by admin frontend
Route::prefix('instrument-type-settings')->name('instrument-type-settings.')->controller(SettingContractController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:instrument_settings.view')->name('index');
    Route::post('/', 'store')->middleware('permission:instrument_settings.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:instrument_settings.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:instrument_settings.edit')->name('update');
});

// FAQ Management
Route::prefix('faqs')->name('faqs.')->controller(FaqController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:faqs.view')->name('index');
    Route::post('/', 'store')->middleware('permission:faqs.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:faqs.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:faqs.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:faqs.delete')->name('destroy');
});

// Instruction / promotional images (صور تعليمية أو إعلانية)
Route::prefix('instruction-sections')->name('instruction-sections.')->controller(InstructionSectionController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:instruction_sections.view')->name('index');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:instruction_sections.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:instruction_sections.edit')->name('update');
    Route::post('/{id}/toggle', 'toggle')->whereNumber('id')->middleware('permission:instruction_sections.edit')->name('toggle');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:instruction_sections.delete')->name('destroy');
    Route::post('/{id}/images', 'uploadImage')->whereNumber('id')->middleware('permission:instruction_sections.edit')->name('images.store');
    Route::post('/{id}/images/{imageId}/delete', 'destroyImage')
        ->whereNumber(['id', 'imageId'])
        ->middleware('permission:instruction_sections.delete')
        ->name('images.destroy');
});

// Message alerts (explanatory messages) — sections & items + alerts CRUD
Route::prefix('message-alert-sections')->name('message-alert-sections.')->middleware('auth:sanctum')->group(function () {
    Route::controller(MessageAlertSectionItemController::class)->group(function () {
        Route::get('{sectionId}/items', 'indexForSection')->whereNumber('sectionId')->middleware('permission:message_alerts.view')->name('items.index');
        Route::post('{sectionId}/items', 'storeForSection')->whereNumber('sectionId')->middleware('permission:message_alerts.create')->name('items.store');
    });
    Route::controller(MessageAlertSectionController::class)->group(function () {
        Route::get('/options/list', 'options')->middleware('permission:message_alerts.view')->name('options');
        Route::get('/{audience}/options/list', 'options')
            ->where('audience', 'client|property|employee')
            ->middleware('permission:message_alerts.view')
            ->name('options.audience');
        Route::get('/', 'index')->middleware('permission:message_alerts.view')->name('index');
        Route::post('/', 'store')->middleware('permission:message_alerts.create')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:message_alerts.view')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:message_alerts.edit')->name('update');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:message_alerts.delete')->name('destroy');
    });
});

Route::prefix('message-alert-section-items')->name('message-alert-section-items.')->controller(MessageAlertSectionItemController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/options/list', 'options')->middleware('permission:message_alerts.view')->name('options');
    Route::get('/{audience}/options/list', 'options')
        ->where('audience', 'client|property|employee')
        ->middleware('permission:message_alerts.view')
        ->name('options.audience');
    Route::get('/', 'index')->middleware('permission:message_alerts.view')->name('index');
    Route::post('/', 'store')->middleware('permission:message_alerts.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:message_alerts.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:message_alerts.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:message_alerts.delete')->name('destroy');
});

Route::prefix('message-alerts')->name('message-alerts.')->controller(MessageAlertController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/types', 'types')->middleware('permission:message_alerts.view')->name('types');
    Route::get('/all', 'all')->middleware('permission:message_alerts.view')->name('all');

    Route::prefix('{audience}')->where(['audience' => 'client|property|employee'])->group(function () {
        Route::get('/create', 'create')->middleware('permission:message_alerts.view')->name('create.audience');
        Route::get('/', 'index')->middleware('permission:message_alerts.view')->name('index.audience');
        Route::post('/', 'store')->middleware('permission:message_alerts.create')->name('store.audience');
        Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:message_alerts.view')->name('show.audience');
        Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:message_alerts.edit')->name('update.audience');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:message_alerts.delete')->name('destroy.audience');
    });

    Route::get('/', 'index')->middleware('permission:message_alerts.view')->name('index');
    Route::post('/', 'store')->middleware('permission:message_alerts.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:message_alerts.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:message_alerts.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:message_alerts.delete')->name('destroy');
});

// Blogs Management
Route::prefix('blogs')->name('blogs.')->controller(BlogController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:blogs.view')->name('index');
    Route::post('/', 'store')->middleware('permission:blogs.create')->name('store');
    Route::get('/{id}', 'show')->middleware('permission:blogs.view')->name('show');
    Route::put('/{id}', 'update')->middleware('permission:blogs.edit')->name('update');
    Route::delete('/{id}', 'destroy')->middleware('permission:blogs.delete')->name('destroy');
    Route::post('/{id}/toggle-active', 'toggleActive')->middleware('permission:blogs.edit')->name('toggle-active');
    Route::get('/statistics', 'statistics')->middleware('permission:blogs.view')->name('statistics');
});

// Manual SMS send (Taqnyat) — employee token
Route::prefix('sms')->name('sms.')->controller(SmsController::class)->middleware(['auth:sanctum', 'permission:sms.create'])->group(function () {
    Route::post('/message', 'sendMessage')->name('message');
    Route::post('/send', 'send')->name('send');
});

// Project-wide SMS message templates (single settings row)
Route::prefix('sms-settings')->name('sms-settings.')->controller(SmsSettingController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'show')->middleware('permission:settings.view')->name('show');
    Route::post('/', 'update')->middleware('permission:settings.edit')->name('update');
});

// Project-wide meter fees (housing/commercial only)
Route::prefix('meter-fee-settings')->name('meter-fee-settings.')->controller(MeterFeeSettingController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'show')->middleware('permission:settings.view')->name('show');
    Route::post('/', 'update')->middleware('permission:settings.edit')->name('update');
});

// App settings (taxes, social, banner, terms & privacy)
Route::prefix('settings')->name('settings.')->controller(SettingController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:settings.view')->name('index');
    Route::post('/', 'update')->middleware('permission:settings.edit')->name('update');
    Route::post('/image-banner', 'updateImageBanner')->middleware('permission:settings.edit')->name('image-banner.update');
    Route::post('/cover', 'updateCover')->middleware('permission:settings.edit')->name('cover.update');
});

// General settings toggles (/home/settings page: website, stores, thank-you card)
Route::prefix('settings/general')->name('settings.general.')
    ->controller(GeneralSettingController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'index')->middleware('permission:settings.view')->name('index');
        Route::put('/', 'bulkUpdate')->middleware('permission:settings.edit')->name('bulk-update');
        Route::put('/{key}', 'update')->middleware('permission:settings.edit')->name('update');
    });

Route::prefix('settings/app-status')->name('settings.app-status.')
    ->controller(AppStatusController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'show')->middleware('permission:settings.view')->name('show');
        Route::match(['put', 'post', 'patch'], '/', 'update')->middleware('permission:settings.edit')->name('update');
    });

// App content dashboard (payment methods, legal pages, customer messages)
Route::prefix('app-content')->name('app-content.')->controller(AppContentOverviewController::class)->middleware(['auth:sanctum', 'permission:app_content.view'])->group(function () {
    Route::get('/overview', 'overview')->name('overview');
});

Route::prefix('payment-types')->name('payment-types.')->controller(PaymentTypeController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:app_content.view')->name('index');
    Route::post('/', 'store')->middleware('permission:app_content.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:app_content.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:app_content.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:app_content.delete')->name('destroy');
});

Route::prefix('customer-messages')->name('customer-messages.')->controller(CustomerApplicationMessageController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/overview', 'overview')->middleware('permission:app_content.view')->name('overview');
    Route::get('/all', 'all')->middleware('permission:app_content.view')->name('all');
    Route::get('/create', 'createForm')->middleware('permission:app_content.view')->name('create');
    Route::get('/', 'index')->middleware('permission:app_content.view')->name('index');
    Route::post('/', 'store')->middleware('permission:app_content.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:app_content.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:app_content.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:app_content.delete')->name('destroy');
});

Route::prefix('content')->name('content.')->controller(PageContentController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/legal-pages', 'legalPages')->middleware('permission:app_content.view')->name('legal-pages');
    Route::get('/terms-and-conditions', 'termsAndConditions')->middleware('permission:app_content.view')->name('terms.show');
    Route::post('/terms-and-conditions', 'updateTermsAndConditions')->middleware('permission:app_content.edit')->name('terms.update');
    Route::get('/privacy', 'privacy')->middleware('permission:app_content.view')->name('privacy.show');
    Route::post('/privacy', 'updatePrivacy')->middleware('permission:app_content.edit')->name('privacy.update');
});

Route::prefix('content-pages')->name('content-pages.')->controller(ContentPageController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/{pageKey}', 'show')->middleware('permission:app_content.view')->name('show');
    Route::post('/{pageKey}', 'upsert')->middleware('permission:app_content.edit')->name('upsert');
});

// Ads Management
Route::prefix('ads')->name('admin-ads.')->controller(AdController::class)->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index')->middleware('permission:ads.view')->name('index');
    Route::post('/', 'store')->middleware('permission:ads.create')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:ads.view')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:ads.edit')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:ads.delete')->name('destroy');
});
