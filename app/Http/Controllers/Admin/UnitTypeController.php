<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\UnitType;
use Illuminate\Http\Request;

class UnitTypeController extends Controller
{
    use Responser;

    /**
     * Display a listing of unit types
     */
    public function index(Request $request)
    {
        try {
            $query = UnitType::query();

            // Filter by contract_type if provided
            if ($request->has('contract_type')) {
                $query->where('contract_type', $request->contract_type);
            }

            // Filter by rooms if provided
            if ($request->has('rooms')) {
                $query->where('rooms', $request->rooms);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                      ->orWhere('name_en', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $unitTypes = $query->paginate($request->get('per_page', 20));

            return $this->apiResponse(
                [
                    'items' => $unitTypes->items(),
                    'pagination' => $this->paginate($unitTypes),
                ],
                trans('api.success')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Show the form for creating a new unit type
     */
    public function create()
    {
        try {
            $data = [
                'validation_rules' => [
                    'name_ar' => 'required|string|max:255',
                    'name_en' => 'nullable|string|max:255',
                    'contract_type' => 'required|in:housing,commercial',
                    'rooms' => 'nullable|in:Room,NoRoom',
                ],
                'contract_type_options' => [
                    ['value' => 'housing', 'label' => 'Housing'],
                    ['value' => 'commercial', 'label' => 'Commercial'],
                ],
                'rooms_options' => [
                    ['value' => 'Room', 'label' => 'Room'],
                    ['value' => 'NoRoom', 'label' => 'No Room'],
                ],
                'fields' => [
                    [
                        'name' => 'name_ar',
                        'label' => 'Arabic Name',
                        'type' => 'text',
                        'required' => true,
                        'max_length' => 255,
                    ],
                    [
                        'name' => 'name_en',
                        'label' => 'English Name',
                        'type' => 'text',
                        'required' => false,
                        'max_length' => 255,
                    ],
                    [
                        'name' => 'contract_type',
                        'label' => 'Contract Type',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            ['value' => 'housing', 'label' => 'Housing'],
                            ['value' => 'commercial', 'label' => 'Commercial'],
                        ],
                    ],
                    [
                        'name' => 'rooms',
                        'label' => 'Rooms',
                        'type' => 'select',
                        'required' => false,
                        'options' => [
                            ['value' => 'Room', 'label' => 'Room'],
                            ['value' => 'NoRoom', 'label' => 'No Room'],
                        ],
                    ],
                ],
            ];

            return $this->apiResponse(
                $data,
                trans('api.success')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created unit type
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name_ar' => 'required|string|max:255',
                'name_en' => 'nullable|string|max:255',
                'contract_type' => 'required|in:housing,commercial',
                'rooms' => 'nullable|in:Room,NoRoom',
            ]);

            $unitType = UnitType::create($validated);

            return $this->apiResponse(
                $unitType,
                trans('api.created_successfully'),
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified unit type
     */
    public function show($id)
    {
        try {
            $unitType = UnitType::find($id);

            if (!$unitType) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            return $this->apiResponse(
                $unitType,
                trans('api.success')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update the specified unit type
     */
    public function update(Request $request, $id)
    {
        try {
            $unitType = UnitType::find($id);

            if (!$unitType) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            $validated = $request->validate([
                'name_ar' => 'sometimes|required|string|max:255',
                'name_en' => 'nullable|string|max:255',
                'contract_type' => 'sometimes|required|in:housing,commercial',
                'rooms' => 'nullable|in:Room,NoRoom',
            ]);

            $unitType->update($validated);

            return $this->apiResponse(
                $unitType->fresh(),
                trans('api.updated_successfully')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified unit type
     */
    public function destroy($id)
    {
        try {
            $unitType = UnitType::find($id);

            if (!$unitType) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            $unitType->delete();

            return $this->apiResponse(
                [],
                trans('api.deleted_successfully')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Search unit types
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'search' => 'required|string|min:1',
            ]);

            $search = $request->search;
            $query = UnitType::query();

            // Search in name_ar and name_en
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%");
                  
            });

            // Optional filters
            if ($request->has('contract_type')) {
                $query->where('contract_type', $request->contract_type);
            }

            if ($request->has('rooms')) {
                $query->where('rooms', $request->rooms);
            }

            $unitTypes = $query->latest()->paginate($request->get('per_page', 20));

            return $this->apiResponse(
                [
                    'items' => $unitTypes->items(),
                    'pagination' => $this->paginate($unitTypes),
                ],
                trans('api.success')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }
}

