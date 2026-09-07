<?php

namespace App\Modules\RealEstate\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\Unit\StoreUnitsRequest;
use App\Http\Resources\Api\V2\UnitResource;
use App\Http\Traits\Responser;
use App\Models\RealEstate;
use App\Models\UnitsReal;
use App\Services\RealEstateUnitsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class UnitEstateController extends Controller
{
    use Responser;

    private function unitEagerLoads(): array
    {
        return ['unitType', 'unitUsage', 'realEstate'];
    }

    private function normalizeUnitBooleanFlags(Request $request): void
    {
        $keys = ['kitchen_tank', 'furnished', 'electricity_meter', 'water_meter'];
        $normalized = [];

        foreach ($keys as $key) {
            if (! $request->exists($key)) {
                continue;
            }

            $value = $request->input($key);
            if ($value === null || $value === '') {
                $normalized[$key] = null;
                continue;
            }

            if (is_bool($value) || is_int($value)) {
                $normalized[$key] = $value;
                continue;
            }

            if (is_string($value)) {
                $trimmed = strtolower(trim($value));
                if (in_array($trimmed, ['0', '1'], true)) {
                    $normalized[$key] = (int) $trimmed;
                    continue;
                }
                if (in_array($trimmed, ['true', 'false'], true)) {
                    $normalized[$key] = $trimmed === 'true' ? 1 : 0;
                }
            }
        }

        if ($request->exists('type_furnished')) {
            $normalized['type_furnished'] = \App\Support\TypeFurnished::normalize($request->input('type_furnished'));
        }

        if ($normalized !== []) {
            $request->merge($normalized);
        }
    }

    public function index($id)
    {
        $userReal = RealEstate::findOrFail($id);
        $user = Auth::user();
        $units = UnitsReal::where('real_estates_units_id', $userReal->id)
            ->where('user_id', $user->id)
            ->with($this->unitEagerLoads())
            ->get();

        return $this->apiResponse(UnitResource::collection($units), trans('api.units'));
    }

    public function all($id)
    {
        $user = Auth::user();

        try {
            $userReal = RealEstate::findOrFail($id);
            $units = UnitsReal::where('real_estates_units_id', $userReal->id)
                ->where('user_id', $user->id)
                ->with($this->unitEagerLoads())
                ->get();

            return $this->apiResponse(UnitResource::collection($units), trans('api.units'), 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorMessage(trans('لا يوجد عقار'), 404);
        } catch (\Exception $e) {
            return $this->errorMessage(trans('حدث خطأ ما'), 500);
        }
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $userUnit = UnitsReal::where('id', $id)
                ->where('user_id', $user->id)
                ->with($this->unitEagerLoads())
                ->firstOrFail();

            return $this->apiResponse(new UnitResource($userUnit), trans('تفاصيل الوحده'), 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorMessage(trans('لا يوجد وحده'), 404);
        } catch (\Exception $e) {
            return $this->errorMessage(trans('حدث خطأ ما'), 500);
        }
    }

    public function create(StoreUnitsRequest $request)
    {
        $user = auth()->user();
        $userId = (int) $user->id;

        $realEstate = RealEstate::query()
            ->where('user_id', $userId)
            ->find((int) $request->real_estates_units_id);

        if (! $realEstate) {
            return $this->errorMessage(trans('لا يوجد عقار'), 404);
        }

        try {
            $units = app(RealEstateUnitsService::class)->attachToRealEstate(
                $realEstate,
                $request->input('units'),
                $userId
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        }

        $allUnits = UnitsReal::query()
            ->where('real_estates_units_id', $realEstate->id)
            ->where('user_id', $userId)
            ->with($this->unitEagerLoads())
            ->get();

        return $this->apiResponse([
            'created' => UnitResource::collection(collect($units)),
            'created_count' => count($units),
            'units' => UnitResource::collection($allUnits),
            'units_count' => $allUnits->count(),
        ], trans('api.created_success'), 201);
    }

    public function update(Request $request, $id)
    {
        $this->normalizeUnitBooleanFlags($request);

        $rules = [
            'real_estates_units_id' => 'sometimes|exists:real_estates,id',
            'unit_type_id' => 'sometimes|exists:unit_types,id',
            'unit_usage_id' => 'nullable|sometimes|exists:unit_usages,id',
            'contract_type' => 'nullable|sometimes|in:housing,commercial',
            'unit_number' => 'sometimes|string|max:255',
            'floor_number' => 'sometimes|integer|max:15',
            'unit_area' => 'sometimes|numeric',
            'tootal_rooms' => 'sometimes|integer|max:10',
            'The_number_of_halls' => 'sometimes|integer|max:10',
            'The_number_of_kitchens' => 'sometimes|integer|max:10',
            'The_number_of_toilets' => 'sometimes|integer|max:10',
            'window_ac' => 'sometimes|max:10',
            'split_ac' => 'sometimes|max:10',
            'electricity_meter_number' => 'nullable|string|max:255',
            'water_meter_number' => 'nullable|string|max:255',
            'kitchen_tank' => 'sometimes|boolean',
            'furnished' => 'sometimes|boolean',
            'type_furnished' => \App\Support\TypeFurnished::rules(true),
            'electricity_meter' => 'sometimes|boolean',
            'water_meter' => 'sometimes|boolean',
            'electricity_meter_ownership' => 'nullable|in:owner,tenant',
            'water_meter_ownership' => 'nullable|in:owner,tenant',
        ];

        $this->validate($request, $rules);
        $units = UnitsReal::findOrFail($id);

        $data = $request->only([
            'unit_type_id',
            'unit_usage_id',
            'contract_type',
            'unit_number',
            'floor_number',
            'unit_area',
            'tootal_rooms',
            'The_number_of_halls',
            'The_number_of_kitchens',
            'The_number_of_toilets',
            'window_ac',
            'split_ac',
            'electricity_meter_number',
            'water_meter_number',
            'real_estates_units_id',
            'kitchen_tank',
            'furnished',
            'type_furnished',
            'electricity_meter',
            'water_meter',
            'electricity_meter_ownership',
            'water_meter_ownership',
        ]);

        $data['user_id'] = auth()->id();

        foreach (['kitchen_tank', 'furnished', 'electricity_meter', 'water_meter'] as $flag) {
            if ($request->exists($flag)) {
                $data[$flag] = (int) $request->boolean($flag);
            }
        }

        foreach (['electricity_meter_ownership', 'water_meter_ownership'] as $ownership) {
            if ($request->exists($ownership)) {
                $value = $request->input($ownership);
                $data[$ownership] = ($value === '' || $value === null) ? null : $value;
            }
        }

        if ($request->exists('type_furnished')) {
            $data['type_furnished'] = \App\Support\TypeFurnished::normalize($request->input('type_furnished'));
        }

        try {
            $units->update(UnitsReal::attributesForApi($data));
            return $this->apiResponse(new UnitResource($units->fresh($this->unitEagerLoads())), trans('api.success'), 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorMessage(trans('api.not_have_unit'), 404);
        } catch (\Exception $e) {
            return $this->errorMessage(trans('api.error'), 500);
        }
    }

    public function delete($id)
    {
        $realEstate = UnitsReal::findOrFail($id);
        $realEstate->delete();
        return $this->successMessage(trans('api.success'), 200);
    }
}

