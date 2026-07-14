<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\SettingContractResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\SettingContract;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingContractController extends Controller
{
    use Responser;

    /**
     * GET /api/admin/setting-contracts
     */
    public function index(Request $request)
    {
        try {
            $query = SettingContract::query();

            if ($request->filled('instrument_type')) {
                $query->where(
                    'instrument_type',
                    Contract::normalizeInstrumentType($request->string('instrument_type')->toString())
                );
            }

            if ($request->has('realestate')) {
                $query->where('realestate', $request->boolean('realestate'));
            }

            if ($request->has('contract')) {
                $query->where('contract', $request->boolean('contract'));
            }

            $items = $query->orderBy('id')->paginate((int) $request->get('per_page', 50));

            return $this->apiResponse([
                'items' => SettingContractResource::collection($items->items()),
                'pagination' => $this->paginate($items),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/setting-contracts
     */
    public function store(Request $request)
    {
        try {
            $setting = $this->persistSetting($request);

            return $this->apiResponse(
                new SettingContractResource($setting),
                trans('api.created_successfully'),
                201
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/setting-contracts/{id}
     */
    public function show(int $id)
    {
        try {
            $setting = SettingContract::query()->findOrFail($id);

            return $this->apiResponse(
                new SettingContractResource($setting),
                trans('api.success')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/setting-contracts/{id}
     */
    public function update(Request $request, int $id)
    {
        try {
            $setting = SettingContract::query()->findOrFail($id);
            $setting = $this->persistSetting($request, $setting);

            return $this->apiResponse(
                new SettingContractResource($setting),
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

    private function persistSetting(Request $request, ?SettingContract $setting = null): SettingContract
    {
        if ($request->filled('instrument_type')) {
            $request->merge([
                'instrument_type' => Contract::normalizeInstrumentType($request->input('instrument_type')),
            ]);
        }

        $validated = $request->validate($this->rules($setting));

        if (array_key_exists('realestate', $validated)) {
            $validated['realestate'] = $request->boolean('realestate');
        }

        if (array_key_exists('contract', $validated)) {
            $validated['contract'] = $request->boolean('contract');
        }

        if ($setting) {
            $setting->update($validated);

            return $setting->fresh();
        }

        return SettingContract::query()->create($validated);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?SettingContract $setting = null): array
    {
        $isUpdate = $setting !== null;

        return [
            'instrument_type' => [
                $isUpdate ? 'sometimes' : 'required',
                Rule::in(Contract::instrumentTypes()),
                Rule::unique('setting_contracts', 'instrument_type')->ignore($setting?->id),
            ],
            'realestate' => ($isUpdate ? 'sometimes|' : 'required|').'boolean',
            'contract' => ($isUpdate ? 'sometimes|' : 'required|').'boolean',
            'label' => 'nullable|string|max:500',
            'sms_user' => 'nullable|string|max:5000',
            'sms_owner' => 'nullable|string|max:5000',
            'sms_employee' => 'nullable|string|max:5000',
        ];
    }
}
