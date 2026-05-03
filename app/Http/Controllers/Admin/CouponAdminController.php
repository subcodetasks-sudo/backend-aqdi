<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CouponAdminController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = Coupon::query();

            if (! $request->boolean('with_deleted')) {
                $query->where('is_delete', false);
            }

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code_coupon', 'like', "%{$search}%");
                });
            }

            if ($request->filled('type_coupon')) {
                $query->where('type_coupon', $request->string('type_coupon'));
            }

            if ($request->filled('is_review')) {
                $query->where('is_review', $request->boolean('is_review'));
            }

            $coupons = $query->latest()->paginate((int) $request->get('per_page', 20));

            return $this->apiResponse([
                'items' => $coupons->items(),
                'pagination' => $this->paginate($coupons),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $coupon = Coupon::query()->create(
                $request->validate($this->rules())
            );

            return $this->apiResponse($coupon, trans('api.created_successfully'), 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $coupon = Coupon::query()->findOrFail($id);

            return $this->apiResponse($coupon, trans('api.success'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $coupon = Coupon::query()->findOrFail($id);
            $coupon->update($request->validate($this->rules(true, $coupon->id)));

            return $this->apiResponse($coupon->fresh(), trans('api.updated_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $coupon = Coupon::query()->findOrFail($id);
            $coupon->update(['is_delete' => true]);

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    private function rules(bool $isUpdate = false, ?int $couponId = null): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return [
            'name' => "{$required}|string|max:255",
            'code_coupon' => [
                $required,
                'string',
                'max:255',
                Rule::unique('coupons', 'code_coupon')->ignore($couponId),
            ],
            'type_coupon' => "{$required}|in:ratio,value",
            'value_coupon' => "{$required}|numeric|min:0",
            'date_start' => "{$required}|date",
            'date_end' => "{$required}|date|after_or_equal:date_start",
            'usage' => "{$required}|integer|min:0",
            'usage_of_user' => "{$required}|integer|min:0",
            'is_review' => 'nullable|boolean',
            'is_delete' => 'nullable|boolean',
        ];
    }
}
