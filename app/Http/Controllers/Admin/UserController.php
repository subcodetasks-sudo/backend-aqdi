<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserDiscountRequest;
use App\Http\Resources\Admin\V2\Api\AllUserResource;
use App\Http\Resources\Admin\V2\Api\CustomDiscountResource;
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
    protected function usersDashboardQuery(Request $request)
    {
        $query = $this->usersWithTotalPaidQuery();
        $this->applyDashboardRelations($query);

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

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\User>  $query
     */
    private function applyDashboardRelations($query): void
    {
        $query->withCount([
            'contracts as completed_orders_count' => fn ($q) => $q->notDeleted()->where('is_completed', 1),
            'contracts as draft_orders_count' => fn ($q) => $q->notDeleted()->where('is_draft', true),
            'contracts as incomplete_orders_count' => fn ($q) => $q->notDeleted()->where('is_completed', 0),
            'realEstate as real_estate_count',
            'unitReal as units_count',
        ])->with([
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
