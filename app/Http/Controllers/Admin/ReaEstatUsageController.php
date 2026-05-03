<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\ReaEstatUsage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReaEstatUsageController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = ReaEstatUsage::query();

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

            $usages = $query->latest()->paginate((int) $request->get('per_page', 20));

            return $this->apiResponse([
                'items' => $usages->items(),
                'pagination' => $this->paginate($usages),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $usage = ReaEstatUsage::query()->create(
                $request->validate($this->rules())
            );

            return $this->apiResponse($usage, trans('api.created_successfully'), 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $usage = ReaEstatUsage::query()->findOrFail($id);
            return $this->apiResponse($usage, trans('api.success'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $usage = ReaEstatUsage::query()->findOrFail($id);
            $usage->update($request->validate($this->rules(true)));

            return $this->apiResponse($usage->fresh(), trans('api.updated_successfully'));
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
            $usage = ReaEstatUsage::query()->findOrFail($id);
            $usage->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return [
            'name_ar' => "{$required}|string|max:255",
            'name_en' => 'nullable|string|max:255',
            'contract_type' => "{$required}|in:housing,commercial",
        ];
    }
}
