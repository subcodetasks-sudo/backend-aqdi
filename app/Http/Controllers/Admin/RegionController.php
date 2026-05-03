<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegionController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = Region::query()->withCount('city');

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            }

            $perPage = max((int) $request->get('per_page', 20), 1);
            $regions = $query->latest()->paginate($perPage);

            return $this->apiResponse(
                [
                    'items' => $regions->items(),
                    'pagination' => $this->paginate($regions),
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
                'name_ar' => ['required', 'string', 'max:255', Rule::unique('regions', 'name_ar')],
                'name_en' => ['nullable', 'string', 'max:255'],
            ]);

            $region = Region::query()->create($validated);

            return $this->apiResponse($region, trans('api.created_successfully'), 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $region = Region::query()
                ->with('city')
                ->withCount('city')
                ->findOrFail($id);

            return $this->apiResponse($region, trans('api.success'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $region = Region::query()->findOrFail($id);

            $validated = $request->validate([
                'name_ar' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('regions', 'name_ar')->ignore($region->id)],
                'name_en' => ['nullable', 'string', 'max:255'],
            ]);

            $region->update($validated);

            return $this->apiResponse($region->fresh(), trans('api.updated_successfully'));
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
            $region = Region::query()->findOrFail($id);
            $region->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }
}
