<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\UnitUsage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UnitUsageController extends Controller
{
    use Responser;

    /**
     * Display a listing of unit usages.
     */
    public function index(Request $request)
    {
        try {
            $query = UnitUsage::query();

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

            $allowedSorts = ['id', 'name_ar', 'contract_type', 'created_at', 'updated_at'];
            $sortBy = $request->get('sort_by', 'created_at');
            $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';

            $sortOrder = strtolower($request->get('sort_order', 'desc'));
            $sortOrder = in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'desc';

            $perPage = (int) $request->get('per_page', 20);
            $perPage = $perPage > 0 ? $perPage : 20;

            $unitUsages = $query
                ->orderBy($sortBy, $sortOrder)
                ->paginate($perPage);

            return $this->apiResponse(
                [
                    'items' => $unitUsages->items(),
                    'pagination' => $this->paginate($unitUsages),
                ],
                trans('api.success')
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created unit usage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->rules());

            $unitUsage = UnitUsage::create($validated);

            return $this->apiResponse($unitUsage, trans('api.created_successfully'), 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified unit usage.
     */
    public function show(int $id)
    {
        try {
            $unitUsage = UnitUsage::query()->findOrFail($id);

            return $this->apiResponse($unitUsage, trans('api.success'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified unit usage.
     */
    public function update(Request $request, int $id)
    {
        try {
            $unitUsage = UnitUsage::query()->findOrFail($id);
            $validated = $request->validate($this->rules(true));

            $unitUsage->update($validated);

            return $this->apiResponse($unitUsage->fresh(), trans('api.updated_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified unit usage.
     */
    public function destroy(int $id)
    {
        try {
            $unitUsage = UnitUsage::query()->findOrFail($id);
            $unitUsage->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show validation rules to client (if your frontend needs them).
     */
    public function create()
    {
        try {
            return $this->apiResponse(
                [
                    'validation_rules' => $this->rules(),
                ],
                trans('api.success')
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validation rules.
     */
    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return [
            'name_ar'        => "{$required}|string|max:255",
            'name_en'        => 'nullable|string|max:255',
            'contract_type'  => "{$required}|in:housing,commercial",
        ];
    }
}
