<?php

use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\AppContentOverviewController;
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

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/employee-salary', 'employeeSalary')->name('employee-salary');
        Route::get('/employee-notes', 'employeeNotes')->name('employee-notes');
        Route::post('/fcm', 'updateFcmToken')->name('fcm');
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

Route::prefix('employees')->name('employees.')->middleware('auth:sanctum')->controller(EmployeeKpiController::class)->group(function () {
    Route::get('/kpis', 'index')->name('kpis.index');
    Route::get('/me/kpis', 'me')->name('kpis.me');
    Route::get('/{id}/kpis/details', 'details')->whereNumber('id')->name('kpis.details');
    Route::get('/{id}/kpis', 'show')->whereNumber('id')->name('kpis.show');
});

// Firebase notifications
Route::prefix('notifications')->name('notifications.')->controller(NotificationController::class)
    ->middleware('auth:sanctum')
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
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{uuid}', 'show')->name('show');
    Route::match(['post', 'put', 'patch'], '/{uuid}', 'update')->name('update');
});

Route::prefix('analytics/refunds')->name('analytics.refunds.')
    ->controller(RefundableContractController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/contracts', 'index')->name('contracts.index');
        // Body-based confirm (avoids hosting WAF 403 on POST .../contracts/{uuid})
        Route::post('/contracts/confirm', 'confirm')->name('contracts.confirm');
        Route::get('/contracts/{uuid}', 'show')->name('contracts.show');
        Route::match(['post', 'put', 'patch'], '/contracts/{uuid}', 'update')->name('contracts.update');
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

// Operating expenses (مصاريف تشغيلية)
Route::prefix('operating-expenses')->name('operating-expenses.')
    ->controller(OperatingExpenseController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'index')->middleware('permission:analytics.view')->name('index');
        Route::post('/', 'store')->middleware('permission:analytics.create')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:analytics.view')->name('show');
        Route::post('/{id}', 'update')->whereNumber('id')->middleware('permission:analytics.edit')->name('update');
        Route::put('/{id}', 'update')->whereNumber('id')->middleware('permission:analytics.edit')->name('update.put');
        Route::post('/{id}/delete', 'destroy')->whereNumber('id')->middleware('permission:analytics.delete')->name('destroy');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware('permission:analytics.delete')->name('destroy.delete');
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
        Route::post('/marketing/spend', 'importAdSpend')->middleware('permission:analytics.edit')->name('marketing.spend');
        Route::post('/marketing/sync', 'syncAdSpend')->middleware('permission:analytics.edit')->name('marketing.sync');
    });

// Employee-recorded contract payments (ClickPay link on create)
Route::prefix('contract-paid-by-employees')->name('contract-paid-by-employees.')
    ->controller(ContractPaidByEmployeeController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}/payment-link', 'paymentLink')->whereNumber('id')->name('payment-link');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    });

// Received contract: which employee received the contract (`received_contracts` table)
Route::prefix('received-contracts')->name('received-contracts.')->controller(ReceivedContractController::class)->middleware('auth:sanctum')->group(function () {
    Route::post('/', 'store')->name('store');
    Route::patch('{contractId}', 'update')->whereNumber('contractId')->name('update');
    Route::get('{contractId}', 'show')->whereNumber('contractId')->name('show');
});

// Contracts lists
Route::prefix('contracts')->name('contracts.')->controller(OrderController::class)->group(function () {
    Route::get('/draft', 'draftContracts')->name('draft');
    Route::get('/draft/status/{statusId}', 'draftByStatus')->whereNumber('statusId')->name('draft-by-status');
    Route::get('/completed', 'complete')->name('completed');
    Route::get('/completed-draft', 'completedAndDraft')->name('completed-draft');
    Route::get('/received', 'receivedOrders')->name('received');
});

// Orders Management
Route::prefix('orders')->name('orders.')->controller(OrderController::class)->group(function () {
    Route::get('/', 'orders')->name('index');
    Route::get('/return', 'returnOrders')->name('return');
    Route::get('/received', 'receivedOrders')->name('received');
    Route::get('/status/{statusId}', 'byStatus')->whereNumber('statusId')->name('by-status');
    Route::get('/completed', 'complete')->name('completed');
    Route::get('/draft', 'draftContracts')->name('draft');
    Route::get('/draft/status/{statusId}', 'draftByStatus')->whereNumber('statusId')->name('draft-by-status');
    Route::get('/completed-draft', 'completedAndDraft')->name('completed-draft');
    Route::get('/incomplete/list', 'incomplete')->name('incomplete');
    Route::get('/complete/list', 'complete')->name('complete');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->name('update');
    Route::post('/{id}/status', 'updateStatus')->whereNumber('id')->name('update-status');
    Route::post('/{id}/contract-status', 'updateContractStatus')->whereNumber('id')->name('update-contract-status');
    Route::post('/{id}/draft-contract-status', 'updateDraftContractStatus')->whereNumber('id')->name('update-draft-contract-status');
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

// Contract units (multi-unit via contract_units)
Route::prefix('orders/{contractId}/units')
    ->name('orders.units.')
    ->controller(ContractUnitController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'index')->whereNumber('contractId')->name('index');
        Route::post('/', 'store')->whereNumber('contractId')->name('store');
        Route::post('/sync', 'sync')->whereNumber('contractId')->name('sync');
        Route::get('/{unitId}', 'show')->whereNumber('contractId')->whereNumber('unitId')->name('show');
        Route::post('/{unitId}', 'update')->whereNumber('contractId')->whereNumber('unitId')->name('update');
        Route::post('/{unitId}/delete', 'destroy')->whereNumber('contractId')->whereNumber('unitId')->name('destroy');
    });

// Orders Filtering
Route::prefix('orders')->name('orders.')->controller(FilterContract::class)->group(function () {
    Route::get('/filter', 'filter')->name('filter');
});

// Users Management
Route::prefix('users')->name('users.')->controller(UserController::class)->group(function () {
    Route::get('/export', 'export')->name('export');
    Route::get('/', 'allusers')->name('index');
    Route::get('/new', 'newcommersUser')->name('new');
    Route::get('/contracts-complete', 'usersCompleteContracts')->name('contracts-complete');
    Route::get('/{id}/properties/{propertyId}/deed', 'downloadDeed')->whereNumber('id')->whereNumber('propertyId')->name('properties.deed');
    Route::get('/{id}/properties', 'properties')->whereNumber('id')->name('properties.index');
    Route::delete('/{id}/properties/{propertyId}', 'destroyProperty')->whereNumber('id')->whereNumber('propertyId')->name('properties.destroy');
    Route::delete('/{id}/units/{unitId}', 'destroyUnit')->whereNumber('id')->whereNumber('unitId')->name('units.destroy');
    Route::post('/{id}/discount', 'applyDiscount')->whereNumber('id')->name('discount');
    Route::get('/{id}/coupons', 'coupons')->whereNumber('id')->name('coupons.index');
    Route::post('/{id}/coupons', 'storeCoupon')->whereNumber('id')->name('coupons.store');
    Route::get('/{id}/coupons/{couponId}', 'showCoupon')->whereNumber('id')->whereNumber('couponId')->name('coupons.show');
    Route::post('/{id}/coupons/{couponId}/deactivate', 'deactivateCoupon')->whereNumber('id')->whereNumber('couponId')->name('coupons.deactivate');
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

// Tenant roles (صفات المستأجر)
Route::prefix('tenant-roles')->name('tenant-roles.')->controller(TenantRoleController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
});

// Tenant roles (صفات المستأجر)
Route::prefix('tenant-roles')->name('tenant-roles.')->controller(TenantRoleController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
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

// Draft Contract Statuses (مسودات) — same shape as contract-statuses
Route::prefix('draft-contract-statuses')->name('draft-contract-statuses.')->controller(DraftContractStatusController::class)->group(function () {
    Route::get('/active', 'active')->name('active');
    Route::post('/sync', 'syncFromContractStatuses')->name('sync');
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
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

// Popup Contract Management
Route::prefix('popup-contracts')->name('popup-contracts.')->controller(PopupContractController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
});

// Payment success / failed messages
Route::prefix('payment-messages')->name('payment-messages.')->controller(PaymentMessageController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->name('update');
    Route::post('/{id}/delete', 'destroy')->whereNumber('id')->name('destroy');
});

// Contract settings per instrument type (SMS + buttons)
Route::prefix('setting-contracts')->name('setting-contracts.')->controller(SettingContractController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->name('update');
});

// Alias used by admin frontend
Route::prefix('instrument-type-settings')->name('instrument-type-settings.')->controller(SettingContractController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}', 'update')->whereNumber('id')->name('update');
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

// Manual SMS send (Taqnyat) — employee token
Route::prefix('sms')->name('sms.')->controller(SmsController::class)->middleware('auth:sanctum')->group(function () {
    Route::post('/message', 'sendMessage')->name('message');
    Route::post('/send', 'send')->name('send');
});

// Project-wide SMS message templates (single settings row)
Route::prefix('sms-settings')->name('sms-settings.')->controller(SmsSettingController::class)->group(function () {
    Route::get('/', 'show')->name('show');
    Route::post('/', 'update')->name('update');
});

// Project-wide meter fees (housing/commercial only)
Route::prefix('meter-fee-settings')->name('meter-fee-settings.')->controller(MeterFeeSettingController::class)->group(function () {
    Route::get('/', 'show')->name('show');
    Route::post('/', 'update')->name('update');
});

// App settings (taxes, social, banner, terms & privacy)
Route::prefix('settings')->name('settings.')->controller(SettingController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'update')->name('update');
    Route::post('/image-banner', 'updateImageBanner')->name('image-banner.update');
    Route::post('/cover', 'updateCover')->name('cover.update');
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
