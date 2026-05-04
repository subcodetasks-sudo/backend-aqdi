<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeContractReceivedRequest;
use App\Http\Resources\Admin\V2\Api\ReceivedContractResource;
use App\Http\Traits\Responser;
use App\Enums\ReceivedContractStatus;
use App\Models\Employee;
use App\Models\ReceivedContract;
use Illuminate\Database\QueryException;
use Throwable;

class ReceivedContractController extends Controller
{
    use Responser;

    /**
     * Get the received-contract row for a contract (if any).
     */
    public function show(int $contractId)
    {
        if (! request()->user() instanceof Employee) {
            return $this->errorMessage(trans('api.unauthorized'), 403);
        }

        $received = ReceivedContract::query()
            ->where('contract_id', $contractId)
            ->with('employee')
            ->first();

        if (! $received) {
            return $this->errorMessage(trans('api.received_contract_not_found'), 404);
        }

        return $this->apiResponse(
            new ReceivedContractResource($received),
            trans('api.success')
        );
    }

    /**
     * Register receipt of a contract (one row per contract, first time only).
     * `employee_id` is always the authenticated employee (Bearer token).
     */
    public function store(StoreEmployeeContractReceivedRequest $request)
    {
        try {
            if (! $request->user() instanceof Employee) {
                return $this->errorMessage(trans('api.unauthorized'), 403);
            }

            $contractId = $request->integer('contract_id');

            $existingReceived = ReceivedContract::query()
                ->where('contract_id', $contractId)
                ->with('employee')
                ->first();

            if ($existingReceived) {
                return $this->contractAlreadyReceivedResponse($existingReceived);
            }

            $validated = $request->validated();
            $status = ReceivedContractStatus::Pending;
            if (array_key_exists('status', $validated) && $validated['status'] !== null) {
                $status = $validated['status'] instanceof ReceivedContractStatus
                    ? $validated['status']
                    : ReceivedContractStatus::from((string) $validated['status']);
            }

            /** @var Employee $employee */
            $employee = $request->user();

            $dateReceived = $request->filled('date_of_received')
                ? $request->date('date_of_received')->format('Y-m-d')
                : now()->toDateString();

            $attributes = [
                'contract_id' => $contractId,
                'employee_id' => $employee->id,
                'status' => $status,
                'date_of_received' => $dateReceived,
            ];
            if ($request->has('notes')) {
                $attributes['notes'] = $request->input('notes');
            }

            try {
                $received = ReceivedContract::query()->create($attributes);
            } catch (QueryException $queryException) {
                if ($this->isUniqueContractIdViolation($queryException)) {
                    $conflictRow = ReceivedContract::query()
                        ->where('contract_id', $contractId)
                        ->with('employee')
                        ->first();

                    if ($conflictRow) {
                        return $this->contractAlreadyReceivedResponse($conflictRow);
                    }
                }

                throw $queryException;
            }

            $received->load('employee');

            return $this->apiResponse(
                new ReceivedContractResource($received),
                trans('api.success')
            );
        } catch (Throwable $throwable) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $throwable->getMessage(),
                500
            );
        }
    }

    /**
     * Conflict: a receipt row already exists for this contract (any employee).
     * `data` uses the same shape as a successful store (ReceivedContractResource).
     */
    private function contractAlreadyReceivedResponse(ReceivedContract $receivedContract)
    {
        $receivedContract->loadMissing('employee');

        return response()->json([
            'message' => trans('api.contract_already_received'),
            'code' => 409,
            'success' => false,
            'data' => (new ReceivedContractResource($receivedContract))->resolve(request()),
        ], 409);
    }

    private function isUniqueContractIdViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        if ($sqlState === '23505') {
            return true;
        }

        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return $driverCode === 1062 || $driverCode === 19;
    }
}
