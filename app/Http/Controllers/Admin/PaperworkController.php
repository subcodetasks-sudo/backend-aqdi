<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\PaperworkResource;
use App\Http\Traits\Responser;
use App\Models\Paperwork;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaperworkController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = Paperwork::query();

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

            $paperworks = $query->latest()->paginate((int) $request->get('per_page', 20));

            return $this->apiResponse([
                'items' => PaperworkResource::collection($paperworks->items()),
                'pagination' => $this->paginate($paperworks),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $paperwork = $this->persistPaperwork($request);

            return $this->apiResponse(
                new PaperworkResource($paperwork),
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
            $paperwork = Paperwork::query()->findOrFail($id);

            return $this->apiResponse(
                new PaperworkResource($paperwork),
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
            $paperwork = Paperwork::query()->findOrFail($id);
            $paperwork = $this->persistPaperwork($request, $paperwork);

            return $this->apiResponse(
                new PaperworkResource($paperwork),
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
            $paperwork = Paperwork::query()->findOrFail($id);
            $this->deleteIconFile($paperwork);
            $paperwork->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function persistPaperwork(Request $request, ?Paperwork $paperwork = null): Paperwork
    {
        $validated = $request->validate($this->rules($paperwork !== null));

        if ($request->hasFile('icon')) {
            if ($paperwork?->icon) {
                $this->deleteIconFile($paperwork);
            }
            $validated['icon'] = fileUploader($request->file('icon'), 'paperworks');
        }

        if ($paperwork) {
            $paperwork->update($validated);

            return $paperwork->fresh();
        }

        return Paperwork::query()->create($validated);
    }

    private function deleteIconFile(Paperwork $paperwork): void
    {
        if ($paperwork->icon) {
            deleteFile($paperwork->icon);
        }
    }

    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return [
            'name_ar' => "{$required}|string|max:255",
            'name_en' => 'nullable|string|max:255',
            'contract_type' => "{$required}|in:housing,commercial",
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
