<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\PaymentTypeResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentTypeController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = PaymentType::query();

            if ($request->filled('contract_type')) {
                $query->where('contract_type', $request->string('contract_type'));
            }

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            }

            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = strtolower((string) $request->get('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
            $allowedSort = ['id', 'name_ar', 'name_en', 'contract_type', 'created_at'];
            if (! in_array($sortBy, $allowedSort, true)) {
                $sortBy = 'id';
            }

            $paymentTypes = $query
                ->orderBy($sortBy, $sortOrder)
                ->paginate($this->perPageFromRequest($request));

            return $this->paginatedApiResponse(
                $paymentTypes,
                PaymentTypeResource::collection($paymentTypes)
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $paymentType = PaymentType::query()->create(
                $request->validate($this->rules())
            );

            return $this->apiResponse(
                new PaymentTypeResource($paymentType),
                trans('api.created_successfully'),
                201
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $paymentType = PaymentType::query()->findOrFail($id);

            return $this->apiResponse(
                new PaymentTypeResource($paymentType),
                trans('api.success')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $paymentType = PaymentType::query()->findOrFail($id);
            $paymentType->update($request->validate($this->rules(true)));

            return $this->apiResponse(
                new PaymentTypeResource($paymentType->fresh()),
                trans('api.updated_successfully')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $paymentType = PaymentType::query()->findOrFail($id);

            if (Contract::query()->where('payment_type_id', $paymentType->id)->exists()) {
                return $this->errorMessage(trans('api.payment_type_in_use'), 422);
            }

            $paymentType->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $isUpdate = false): array
    {
        return [
            'name_ar' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'contract_type' => [
                $isUpdate ? 'sometimes' : 'required',
                Rule::in(['housing', 'commercial']),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
