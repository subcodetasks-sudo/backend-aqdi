<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CityController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = City::query()->with('regions');

            if ($request->filled('region_id')) {
                $query->where('region_id', (int) $request->region_id);
            }

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            }

            $perPage = max((int) $request->get('per_page', 20), 1);
            $cities = $query->latest()->paginate($perPage);

            return $this->apiResponse(
                [
                    'items' => $cities->items(),
                    'pagination' => $this->paginate($cities),
                ],
                trans('api.success')
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'region_id' => ['required', 'integer', 'exists:regions,id'],
                'name_ar' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('cities', 'name_ar')->where(fn ($q) => $q->where('region_id', $request->region_id)),
                ],
                'name_en' => ['nullable', 'string', 'max:255'],
            ]);

            $city = City::query()->create($validated);
            $city->load('regions');

            return $this->apiResponse($city, trans('api.created_successfully'), 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $city = City::query()->with('regions')->findOrFail($id);

            return $this->apiResponse($city, trans('api.success'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $city = City::query()->findOrFail($id);
            $regionId = (int) ($request->input('region_id', $city->region_id));

            $validated = $request->validate([
                'region_id' => ['sometimes', 'required', 'integer', 'exists:regions,id'],
                'name_ar' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('cities', 'name_ar')
                        ->where(fn ($q) => $q->where('region_id', $regionId))
                        ->ignore($city->id),
                ],
                'name_en' => ['nullable', 'string', 'max:255'],
            ]);

            $city->update($validated);
            $city->load('regions');

            return $this->apiResponse($city->fresh(), trans('api.updated_successfully'));
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
            $city = City::query()->findOrFail($id);
            $city->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }
}
