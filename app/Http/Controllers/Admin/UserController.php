<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserCouponRequest;
use App\Http\Requests\Admin\StoreUserDiscountRequest;
use App\Http\Resources\Admin\V2\Api\AllUserResource;
use App\Http\Resources\Admin\V2\Api\CustomDiscountResource;
use App\Http\Resources\Admin\V2\Api\UserCouponResource;
use App\Http\Resources\Admin\V2\Api\OrderResource;
use App\Http\Resources\Admin\V2\Api\UserPropertyResource;
use App\Http\Resources\Api\V2\UnitResource;
use App\Http\Resources\RealEstateResource;
use App\Http\Traits\Responser;
use App\Models\ContractUnit;
use App\Models\Payment;
use App\Models\RealEstate;
use App\Models\RefundableContract;
use App\Models\UnitsReal;
use App\Models\User;
use App\Models\UserCoupon;
use App\Services\Admin\UserCouponService;
use App\Services\Admin\UserCustomDiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    use Responser;

    /**
     * Customers dashboard list (matches admin customers table).
     * GET /api/admin/users
     *
     * Query: search, platform, banned, is_active, created_at, per_page, page
     */
    public function allusers(Request $request)
    {
        $usersQuery = $this->usersDashboardQuery($request);

        $createdAtFilter = $request->query('created_at');
        if ($createdAtFilter) {
            $usersQuery = $usersQuery->when(
                in_array($createdAtFilter, ['today', 'week', 'month', 'year'], true),
                function ($query) use ($createdAtFilter) {
                    $now = now();
                    $ranges = [
                        'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                        'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                        'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                        'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
                    ];
                    [$start, $end] = $ranges[$createdAtFilter] ?? [null, null];
                    if ($start && $end) {
                        $query->whereBetween('created_at', [$start, $end]);
                    }
                }
            );
        }

        $users = $usersQuery
            ->latest()
            ->paginate($this->perPageFromRequest($request, 25));

        return $this->paginatedApiResponse(
            $users,
            AllUserResource::collection($users),
            trans('api.success'),
            ['summary' => $this->customersSummary()]
        );
    }

    /**
     * Export customers list using the same filters as GET /api/admin/users.
     * GET /api/admin/users/export
     *
     * Query: search, platform, banned, is_active, created_at, page, per_page
     * If page is omitted, all matching rows are exported (capped at 10000).
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->usersDashboardQuery($request, false)->latest('users.id');

        if ($request->filled('page')) {
            $users = $query->paginate($this->perPageFromRequest($request, 25))->getCollection();
        } else {
            $users = $query->limit(10000)->get();
        }

        $filename = 'clients-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'رقم العميل',
                'الاسم',
                'البريد',
                'الجوال',
                'المنصة',
                'الحالة',
                'مكتمل',
                'مسودة',
                'غير مكتمل',
                'العقارات',
                'الوحدات',
                'المدفوع',
                'المسترجع',
                'الصافي',
                'تاريخ الانضمام',
            ]);

            foreach ($users as $user) {
                $paid = round((float) ($user->total_paid_amount ?? 0), 2);
                $refunded = round((float) ($user->total_refunded_amount ?? 0), 2);
                fputcsv($handle, [
                    $user->customerNumber(),
                    $user->name,
                    $user->email,
                    $user->mobile,
                    $user->platformLabelAr(),
                    $user->is_active ? 'نشط' : 'محظور',
                    (int) ($user->completed_orders_count ?? 0),
                    (int) ($user->draft_orders_count ?? 0),
                    (int) ($user->incomplete_orders_count ?? 0),
                    (int) ($user->real_estate_count ?? 0),
                    (int) ($user->units_count ?? 0),
                    $paid,
                    $refunded,
                    round($paid - $refunded, 2),
                    $user->created_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Single customer with all contracts, real estates, and units.
     * GET /api/admin/users/{id}
     */
    public function show(Request $request, int $id)
    {
        $user = $this->usersWithTotalPaidQuery()
            ->tap(fn ($q) => $this->applyDashboardRelations($q))
            ->find($id);

        if (! $user) {
            return $this->apiResponse(
                null,
                trans('api.user_not_found'),
                false,
                404
            );
        }

        return $this->apiResponse(
            [
                'user' => new AllUserResource($user),
                'contracts' => OrderResource::collection($user->contracts),
                'real_estates' => RealEstateResource::collection($user->realEstate),
                'units' => UnitResource::collection($user->unitReal),
            ],
            trans('api.success')
        );
    }

    /**
     * Custom discount / waiver on a client contract.
     * POST /api/admin/users/{id}/discount
     *
     * Body: contract_id, type (percentage|fixed|waiver), value, reason
     */
    public function applyDiscount(StoreUserDiscountRequest $request, int $id, UserCustomDiscountService $service)
    {
        $user = User::query()->find($id);
        if (! $user) {
            return $this->errorMessage(trans('api.user_not_found'), 404);
        }

        try {
            $discount = $service->apply($user, $request->validated(), $request->user()?->id);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        }

        return $this->apiResponse(
            new CustomDiscountResource($discount),
            trans('api.discount_applied_successfully'),
            201
        );
    }

    /**
     * Assign a secret coupon to a client (first-year fees).
     * POST /api/admin/users/{id}/coupons
     *
     * Body: type (percentage|fixed), value, applies_to (all|housing|commercial),
     * expires_at, reason, notify_on_login, notification_message, secret_code
     */
    public function storeCoupon(StoreUserCouponRequest $request, int $id, UserCouponService $service)
    {
        $user = User::query()->find($id);
        if (! $user) {
            return $this->errorMessage(trans('api.user_not_found'), 404);
        }

        try {
            $coupon = $service->create($user, $request->validated(), $request->user()?->id);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        }

        return $this->apiResponse(
            new UserCouponResource($coupon),
            trans('api.user_coupon_created_successfully'),
            201
        );
    }

    /**
     * GET /api/admin/users/{id}/coupons
     */
    public function coupons(int $id, UserCouponService $service)
    {
        $user = User::query()->find($id);
        if (! $user) {
            return $this->errorMessage(trans('api.user_not_found'), 404);
        }

        return $this->apiResponse(
            UserCouponResource::collection($service->listForUser($user)),
            trans('api.success')
        );
    }

    /**
     * GET /api/admin/users/{id}/coupons/{couponId}
     */
    public function showCoupon(int $id, int $couponId)
    {
        $coupon = UserCoupon::query()
            ->with('coupon')
            ->where('user_id', $id)
            ->whereKey($couponId)
            ->first();

        if (! $coupon) {
            return $this->errorMessage(trans('api.user_coupon_not_found'), 404);
        }

        return $this->apiResponse(new UserCouponResource($coupon), trans('api.success'));
    }

    /**
     * POST /api/admin/users/{id}/coupons/{couponId}/deactivate
     */
    public function deactivateCoupon(int $id, int $couponId, UserCouponService $service)
    {
        $coupon = UserCoupon::query()
            ->with('coupon')
            ->where('user_id', $id)
            ->whereKey($couponId)
            ->first();

        if (! $coupon) {
            return $this->errorMessage(trans('api.user_coupon_not_found'), 404);
        }

        return $this->apiResponse(
            new UserCouponResource($service->deactivate($coupon)),
            trans('api.user_coupon_deactivated_successfully')
        );
    }

    /**
     * Client properties with nested units.
     * GET /api/admin/users/{id}/properties
     */
    public function properties(Request $request, int $id)
    {
        $user = User::query()->find($id);
        if (! $user) {
            return $this->errorMessage(trans('api.user_not_found'), 404);
        }

        $query = RealEstate::query()
            ->where('user_id', $id)
            ->with([
                'propertyType',
                'propertyUsages',
                'tenantEntityCity',
                'tenantEntityRegion',
                'contracts',
                'units.unitType',
                'units.unitUsage',
                'units.contracts',
                'units.linkedContracts',
            ])
            ->latest();

        if (Schema::hasColumn((new RealEstate)->getTable(), 'is_deleted')) {
            $query->where(function ($q) {
                $q->where('is_deleted', 0)->orWhereNull('is_deleted');
            });
        }

        $properties = $query->paginate($this->perPageFromRequest($request, 25));

        return $this->paginatedApiResponse(
            $properties,
            UserPropertyResource::collection($properties),
            trans('api.success')
        );
    }

    /**
     * DELETE /api/admin/users/{id}/properties/{propertyId}
     */
    public function destroyProperty(int $id, int $propertyId)
    {
        $user = User::query()->find($id);
        if (! $user) {
            return $this->errorMessage(trans('api.user_not_found'), 404);
        }

        $property = RealEstate::query()
            ->where('user_id', $id)
            ->whereKey($propertyId)
            ->first();

        if (! $property) {
            return $this->errorMessage(trans('api.real_estate_not_found'), 404);
        }

        if ($this->propertyHasContracts($property)) {
            return $this->errorMessage(trans('api.property_has_contracts'), 422);
        }

        UnitsReal::query()->where('real_estates_units_id', $property->id)->delete();
        $property->delete();

        return $this->apiResponse([], trans('api.deleted_successfully'));
    }

    /**
     * DELETE /api/admin/users/{id}/units/{unitId}
     */
    public function destroyUnit(int $id, int $unitId)
    {
        $user = User::query()->find($id);
        if (! $user) {
            return $this->errorMessage(trans('api.user_not_found'), 404);
        }

        $unit = UnitsReal::query()
            ->whereKey($unitId)
            ->where(function ($q) use ($id) {
                $q->where('user_id', $id)
                    ->orWhereHas('realEstate', fn ($rq) => $rq->where('user_id', $id));
            })
            ->first();

        if (! $unit) {
            return $this->errorMessage(trans('api.unit_not_found'), 404);
        }

        if ($this->unitHasContracts($unit)) {
            return $this->errorMessage(trans('api.unit_has_contracts'), 422);
        }

        $unit->delete();

        return $this->apiResponse([], trans('api.deleted_successfully'));
    }

    /**
     * Download property deed file.
     * GET /api/admin/users/{id}/properties/{propertyId}/deed
     */
    public function downloadDeed(int $id, int $propertyId)
    {
        $property = RealEstate::query()
            ->where('user_id', $id)
            ->whereKey($propertyId)
            ->first();

        if (! $property) {
            return $this->errorMessage(trans('api.real_estate_not_found'), 404);
        }

        $relative = (string) $property->image_instrument;
        if ($relative === '') {
            return $this->errorMessage(trans('api.deed_not_found'), 404);
        }

        $fullPath = $this->resolvePublicFilePath($relative);
        if ($fullPath === null) {
            return redirect()->away(asset('storage/'.ltrim($relative, '/')));
        }

        $downloadName = 'deed-'.$property->id.'.'.pathinfo($fullPath, PATHINFO_EXTENSION);

        return response()->download($fullPath, $downloadName);
    }

    private function propertyHasContracts(RealEstate $property): bool
    {
        if ($property->contracts()->exists()) {
            return true;
        }

        if (Schema::hasTable('contract_units') && ContractUnit::query()->where('real_estate_id', $property->id)->exists()) {
            return true;
        }

        return UnitsReal::query()
            ->where('real_estates_units_id', $property->id)
            ->where(function ($q) {
                $q->whereHas('contracts')
                    ->orWhereHas('linkedContracts');
            })
            ->exists();
    }

    private function unitHasContracts(UnitsReal $unit): bool
    {
        if ($unit->contracts()->exists()) {
            return true;
        }

        return $unit->linkedContracts()->exists();
    }

    private function resolvePublicFilePath(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $path = preg_replace('#^storage/#', '', $path) ?? $path;

        foreach ([
            storage_path('app/public/'.$path),
            public_path('storage/'.$path),
            public_path($path),
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function newcommersUser(Request $request)
    {
        $users = $this->usersDashboardQuery($request)
            ->whereDate('created_at', now()->toDateString())
            ->latest()
            ->paginate($this->perPageFromRequest($request, 25));

        return $this->paginatedApiResponse(
            $users,
            AllUserResource::collection($users),
            trans('api.success'),
            ['summary' => $this->customersSummary()]
        );
    }

    public function usersCompleteContracts()
    {
        $users = $this->usersDashboardQuery(request())
            ->whereHas('contracts', function ($q) {
                $q->where('is_completed', 1);
            })
            ->orderBy('updated_at', 'asc')
            ->get();

        return $this->apiResponse(
            AllUserResource::collection($users),
            trans('api.success')
        );
    }

    public function block($id)
    {
        $user = User::find($id);

        if (! $user) {
            return $this->apiResponse(
                [],
                trans('api.user_not_found'),
                404
            );
        }

        $wasActive = (int) $user->is_active === 1;
        $user->update(['is_active' => $wasActive ? 0 : 1]);
        $user->refresh();

        return $this->apiResponse(
            [
                'is_active' => (bool) $user->is_active,
                'status' => (bool) $user->is_active,
            ],
            $wasActive
                ? trans('api.user_blocked_successfull')
                : trans('api.user_unblocked_successfull')
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\User>
     */
    protected function usersDashboardQuery(Request $request, bool $withLists = true)
    {
        $query = $this->usersWithTotalPaidQuery();
        $this->applyDashboardRelations($query, $withLists);

        if ($request->filled('platform')) {
            $platform = User::normalizePlatform((string) $request->input('platform')) ?? User::PLATFORM_WEBSITE;
            if ($platform === User::PLATFORM_WEBSITE) {
                $query->where(function ($q) {
                    $q->where('platform', User::PLATFORM_WEBSITE)
                        ->orWhereNull('platform')
                        ->orWhere('platform', '');
                });
            } else {
                $query->where('platform', $platform);
            }
        }

        if ($request->filled('banned') && $request->boolean('banned')) {
            $query->where('is_active', 0);
        } elseif ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active') ? 1 : 0);
        }

        if ($request->filled('search')) {
            $this->applyCustomerSearch($query, $request->string('search')->toString());
        }

        $createdAtFilter = $request->query('created_at');
        if (in_array($createdAtFilter, ['today', 'week', 'month', 'year'], true)) {
            $now = now();
            $ranges = [
                'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            ];
            [$start, $end] = $ranges[$createdAtFilter];
            $query->whereBetween('created_at', [$start, $end]);
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\User>  $query
     */
    private function applyDashboardRelations($query, bool $withLists = true): void
    {
        $query->withCount([
            'contracts as completed_orders_count' => fn ($q) => $q->notDeleted()->where('is_completed', 1),
            'contracts as draft_orders_count' => fn ($q) => $q->notDeleted()->where('is_draft', true),
            'contracts as incomplete_orders_count' => fn ($q) => $q->notDeleted()->where('is_completed', 0),
            'realEstate as real_estate_count',
            'unitReal as units_count',
        ]);

        if (! $withLists) {
            return;
        }

        $query->with([
            'realEstate.units',
            'realEstate.propertyType',
            'realEstate.propertyUsages',
            'realEstate.tenantEntityRegion',
            'realEstate.tenantEntityCity',
            'unitReal.unitType',
            'unitReal.unitUsage',
            'contracts' => fn ($q) => $q->notDeleted()
                ->with($this->userContractRelations())
                ->latest(),
        ]);
    }

    /**
     * Users list query including aggregated successful payments and refunds.
     *
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\User>
     */
    protected function usersWithTotalPaidQuery()
    {
        return User::query()->addSelect([
            'total_paid_amount' => Payment::query()
                ->selectRaw('coalesce(sum(payments.amount), 0)')
                ->join('contracts', 'payments.contract_uuid', '=', 'contracts.uuid')
                ->whereColumn('contracts.user_id', 'users.id')
                ->where('payments.status', 'success'),
            'total_refunded_amount' => RefundableContract::query()
                ->selectRaw('coalesce(sum(refund_amount), 0)')
                ->whereColumn('refundable_contracts.user_id', 'users.id')
                ->where('is_refunded', 1),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function customersSummary(): array
    {
        $total = User::query()->count();
        $banned = User::query()->where('is_active', 0)->count();
        $apple = User::query()->where('platform', User::PLATFORM_APPLE_STORE)->count();
        $google = User::query()->where('platform', User::PLATFORM_GOOGLE_PLAY)->count();
        $website = User::query()
            ->where(function ($q) {
                $q->where('platform', User::PLATFORM_WEBSITE)
                    ->orWhereNull('platform')
                    ->orWhere('platform', '');
            })
            ->count();

        return [
            'total_customers' => $total,
            'total_customers_label' => 'إجمالي العملاء',
            'banned' => $banned,
            'banned_label' => 'المحظورون',
            'website_customers' => $website,
            'website_customers_label' => 'عملاء الموقع',
            'google_play_customers' => $google,
            'google_play_customers_label' => 'عملاء قوقل بلاي',
            'apple_store_customers' => $apple,
            'apple_store_customers_label' => 'عملاء أبل ستور',
        ];
    }

    private function applyCustomerSearch($query, string $term): void
    {
        $term = trim($term);
        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';
        $numericId = null;
        $customerId = $term;
        if (preg_match('/^c-?(\d+)$/i', $term, $matches)) {
            $numericId = (int) $matches[1];
            $customerId = $matches[1];
        } elseif (ctype_digit($term)) {
            $numericId = (int) $term;
        }

        $query->where(function ($q) use ($like, $numericId, $customerId, $term) {
            $q->where('fname', 'like', $like)
                ->orWhere('lname', 'like', $like)
                ->orWhereRaw("concat(coalesce(fname,''), ' ', coalesce(lname,'')) like ?", [$like])
                ->orWhere('mobile', 'like', $like)
                ->orWhere('email', 'like', $like);

            if ($numericId !== null) {
                $q->orWhere('users.id', $numericId)
                    ->orWhereHas('contracts', function ($cq) use ($numericId, $customerId, $term) {
                        $cq->where('id', $numericId)
                            ->orWhere('uuid', 'like', '%'.$term.'%')
                            ->orWhere('uuid', 'like', '%'.$customerId.'%');
                    });
            } else {
                $q->orWhereHas('contracts', function ($cq) use ($term) {
                    $cq->where('uuid', 'like', '%'.$term.'%');
                });
            }
        });
    }

    /**
     * @return array<int|string, mixed>
     */
    private function userContractRelations(): array
    {
        return [
            'user',
            'receivedContract.employee',
            'acceptRetrunContractEmployee:id,name',
            'refundableContract',
            'contractStatus',
            'draftContractStatus',
            'contractPayments' => fn ($q) => $q->where('status', 'success'),
        ];
    }

    public function deleteUser($id)
    {
        $user = User::find($id);

        if ($user) {
            $user->delete();

            return $this->apiResponse(
                [],
                trans('api.user_deleted_successfull')
            );
        }

        return $this->apiResponse(
            [],
            trans('api.user_not_found'),
            404
        );
    }
}
