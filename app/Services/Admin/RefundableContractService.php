<?php

namespace App\Services\Admin;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Employee;
use App\Models\RefundableContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundableContractService
{
    public const PERIODS = ['today', 'week', 'month', 'year', 'total'];

    /** Returned orders: GET /api/admin/orders/return */
    public const RETURN_CONTRACT_STATUS_ID = 2;

    public function baseQuery(): Builder
    {
        return RefundableContract::query()
            ->whereHas('contract', fn (Builder $q) => $q
                ->where('contract_status_id', self::RETURN_CONTRACT_STATUS_ID)
                ->where('is_delete', 0)
            )
            ->with([
                'contract.user:id,fname,lname,mobile',
                'contract.contractStatus:id,name,color',
                'employee:id,name',
            ]);
    }

    /**
     * Return-order contract from orders list (contract_status_id = 2).
     */
    public function resolveReturnContract(int $contractId): Contract
    {
        $contract = Contract::query()
            ->whereKey($contractId)
            ->where('is_delete', 0)
            ->where('contract_status_id', self::RETURN_CONTRACT_STATUS_ID)
            ->first();

        if (! $contract) {
            throw new InvalidArgumentException(trans('api.refund_contract_must_be_return_status'));
        }

        return $contract;
    }

    public function applyPeriod(Builder $query, string $period): Builder
    {
        if (! in_array($period, self::PERIODS, true)) {
            throw new InvalidArgumentException("Unknown period: {$period}");
        }

        return match ($period) {
            'today' => $query->whereDate('refundable_contracts.created_at', Carbon::today()),
            'week' => $query->whereBetween('refundable_contracts.created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]),
            'month' => $query->whereBetween('refundable_contracts.created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ]),
            'year' => $query->whereBetween('refundable_contracts.created_at', [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ]),
            'total' => $query,
        };
    }

    public function resolvePeriod(?string $period): string
    {
        $period = $period ?: 'today';

        if (! in_array($period, self::PERIODS, true)) {
            throw new InvalidArgumentException('period must be one of: '.implode(', ', self::PERIODS));
        }

        return $period;
    }

    /**
     * Period-scoped query for summaries (without list-only filters).
     */
    public function periodQuery(string $period): Builder
    {
        $query = $this->baseQuery();
        $this->applyPeriod($query, $period);

        return $query;
    }

    /**
     * Cards above the refunds table (موافقة الإدارة).
     *
     * @return array<string, mixed>
     */
    public function getManagementApprovalSummary(Builder $periodQuery): array
    {
        $approved = (clone $periodQuery)->where('refundable_contracts.admin_confirmed', true)->count();
        $notApproved = (clone $periodQuery)->where('refundable_contracts.admin_confirmed', false)->count();

        return [
            'approved' => [
                'key' => 'approved',
                'label_ar' => 'تمت الموافقة',
                'label_en' => 'Approved',
                'count' => $approved,
            ],
            'not_approved' => [
                'key' => 'not_approved',
                'label_ar' => 'لم تتم الموافقة',
                'label_en' => 'Not approved',
                'count' => $notApproved,
            ],
            'total' => $approved + $notApproved,
        ];
    }

    /**
     * Counts per contract_status from `contract_statuses` for refundable rows in period.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getContractStatusSummary(Builder $periodQuery): array
    {
        $countsByStatus = (clone $periodQuery)
            ->join('contracts', 'refundable_contracts.contract_id', '=', 'contracts.id')
            ->whereNotNull('contracts.contract_status_id')
            ->select('contracts.contract_status_id', DB::raw('COUNT(*) as contracts_count'))
            ->groupBy('contracts.contract_status_id')
            ->pluck('contracts_count', 'contract_status_id');

        $statuses = ContractStatus::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'color', 'color_text']);

        if ($statuses->isEmpty()) {
            $statuses = ContractStatus::query()->orderBy('id')->get(['id', 'name', 'color', 'color_text']);
        }

        return $statuses->map(fn (ContractStatus $status) => [
            'contract_status_id' => $status->id,
            'name' => $status->name,
            'color' => $status->color,
            'color_text' => $status->color_text,
            'count' => (int) ($countsByStatus[$status->id] ?? 0),
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildIndexSummary(string $period): array
    {
        $periodQuery = $this->periodQuery($period);

        return [
            'management_approval' => $this->getManagementApprovalSummary($periodQuery),
            'contract_statuses' => $this->getContractStatusSummary($periodQuery),
        ];
    }

    /**
     * Resolve contract id from draft/order number (e.g. "000042" → 42).
     */
    public function resolveContractIdFromDraftNumber(string $draftNumber): ?int
    {
        $digits = preg_replace('/\D/', '', $draftNumber) ?? '';

        if ($digits === '') {
            return null;
        }

        $id = (int) ltrim($digits, '0');

        if ($id < 1) {
            $id = (int) $digits;
        }

        return $id > 0 ? $id : null;
    }

    /**
     * Resolve contract id from store payload (orders return list or draft number).
     */
    public function resolveContractIdFromPayload(array $data): int
    {
        if (! empty($data['contract_id'])) {
            return (int) $data['contract_id'];
        }

        $contractId = $this->resolveContractIdFromDraftNumber((string) ($data['draft_contract_number'] ?? ''));

        if ($contractId === null) {
            throw new InvalidArgumentException(trans('api.invalid_draft_contract_number'));
        }

        return $contractId;
    }

    /**
     * Employee refund request (طلب إسترجاع) for a return order (status = 2).
     *
     * @param  array{contract_id?: int, draft_contract_number?: string, refund_amount: float|int|string, notes?: string|null}  $data
     */
    public function createRefundRequest(Employee $employee, array $data): RefundableContract
    {
        $contract = $this->resolveReturnContract($this->resolveContractIdFromPayload($data));

        $pendingExists = RefundableContract::query()
            ->where('contract_id', $contract->id)
            ->where('admin_confirmed', false)
            ->where('is_refunded', false)
            ->exists();

        if ($pendingExists) {
            throw new InvalidArgumentException(trans('api.refund_request_already_exists'));
        }

        $hasDraft = filled($contract->draft_before_paid) || filled($contract->draft_after_paid);

        $record = RefundableContract::query()->create([
            'contract_id' => $contract->id,
            'employee_id' => $employee->id,
            'has_draft_contract' => $hasDraft,
            'refund_amount' => $data['refund_amount'],
            'notes' => $data['notes'] ?? null,
            'admin_confirmed' => false,
            'is_refunded' => false,
        ]);

        return $this->baseQuery()->findOrFail($record->id);
    }

    /**
     * Admin: update refund data and/or management approval (موافقة الإدارة).
     *
     * @param  array{admin_confirmed?: bool, refund_amount?: float|int|string|null, notes?: string|null}  $data
     */
    public function applyAdminUpdate(RefundableContract $record, array $data): RefundableContract
    {
        $updates = [];

        if (array_key_exists('refund_amount', $data) && $data['refund_amount'] !== null) {
            $updates['refund_amount'] = $data['refund_amount'];
        }

        if (array_key_exists('notes', $data)) {
            $updates['notes'] = $data['notes'];
        }

        if (array_key_exists('admin_confirmed', $data)) {
            $approved = (bool) $data['admin_confirmed'];
            $updates['admin_confirmed'] = $approved;
            $updates['is_refunded'] = $approved;
        }

        if ($updates !== []) {
            $record->update($updates);
        }

        if (array_key_exists('admin_confirmed', $data)) {
            $this->syncContractAfterRefundDecision($record, (bool) $data['admin_confirmed']);
        }

        return $this->baseQuery()->findOrFail($record->id);
    }

    /**
     * Keep return order status on linked contract after admin decision.
     */
    protected function syncContractAfterRefundDecision(RefundableContract $record, bool $approved): void
    {
        $contract = Contract::query()
            ->whereKey($record->contract_id)
            ->where('is_delete', 0)
            ->first();

        if (! $contract) {
            return;
        }

        $contract->update([
            'contract_status_id' => self::RETURN_CONTRACT_STATUS_ID,
            'updated_at' => now(),
        ]);
    }
}
