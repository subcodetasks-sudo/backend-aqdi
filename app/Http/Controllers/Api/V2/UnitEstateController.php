<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
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

    public function create(Request $request)
    {
        $user = auth()->user();

        // Multi-unit payload: { real_estates_units_id, units: [...] }
        if ($request->filled('units') && is_array($request->input('units'))) {
            return $this->createMany($request, (int) $user->id);
        }

        $this->normalizeUnitBooleanFlags($request);

        $rules = [
            'real_estates_units_id' => 'required|exists:real_estates,id',
            'unit_type_id' => 'required|exists:unit_types,id',
            'unit_usage_id' => 'nullable|exists:unit_usages,id',
            'contract_type' => 'nullable|in:housing,commercial',
            'unit_number' => 'required|string',
            'floor_number' => 'required|integer',
            'unit_area' => 'required|numeric',
            'tootal_rooms' => 'nullable|integer',
            'The_number_of_halls' => 'nullable|integer',
            'The_number_of_kitchens' => 'nullable|integer',
            'The_number_of_toilets' => 'nullable|integer',
            'window_ac' => 'nullable|integer',
            'split_ac' => 'nullable|integer',
            'electricity_meter_number' => 'nullable|string|max:255',
            'water_meter_number' => 'nullable|string|max:255',
            'kitchen_tank' => 'nullable|boolean',
            'furnished' => 'nullable|boolean',
            'type_furnished' => \App\Support\TypeFurnished::rules(),
            'electricity_meter' => 'nullable|boolean',
            'water_meter' => 'nullable|boolean',
            'electricity_meter_ownership' => 'nullable|in:owner,tenant',
            'water_meter_ownership' => 'nullable|in:owner,tenant',
            'Number_parking_spaces' => 'nullable|string|max:255',
        ];

        $this->validate($request, $rules);

        $realEstate = RealEstate::query()
            ->where('user_id', $user->id)
            ->findOrFail((int) $request->real_estates_units_id);

        try {
            $units = app(RealEstateUnitsService::class)->attachToRealEstate(
                $realEstate,
                [$request->only([
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
                    'kitchen_tank',
                    'furnished',
                    'type_furnished',
                    'electricity_meter',
                    'water_meter',
                    'electricity_meter_ownership',
                    'water_meter_ownership',
                    'Number_parking_spaces',
                ])],
                (int) $user->id
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        }

        $created = $units[0];

        return response()->json([
            'message' => trans('api.created_success'),
            'code' => 201,
            'success' => true,
            'data' => new UnitResource($created),
            'units_count' => 1,
        ], 201);
    }

    /**
     * Create / attach one or more units in a single request.
     */
    private function createMany(Request $request, int $userId)
    {
        $unitsInput = $request->input('units', []);
        if (! is_array($unitsInput) || $unitsInput === []) {
            return $this->errorMessage('يجب إرسال وحدة واحدة على الأقل.', 422);
        }

        $normalizedUnits = [];
        foreach ($unitsInput as $unit) {
            if (! is_array($unit)) {
                continue;
            }
            $normalizedUnits[] = $this->normalizeUnitItemArray($unit);
        }

        $request->merge(['units' => $normalizedUnits]);

        $this->validate($request, [
            'real_estates_units_id' => 'required|exists:real_estates,id',
            'units' => 'required|array|min:1|max:50',
            'units.*.unit_type_id' => 'required|integer|exists:unit_types,id',
            'units.*.unit_usage_id' => 'nullable|integer|exists:unit_usages,id',
            'units.*.contract_type' => 'nullable|in:housing,commercial',
            'units.*.unit_number' => 'required|string|max:255',
            'units.*.floor_number' => 'required|integer',
            'units.*.unit_area' => 'required|numeric',
            'units.*.tootal_rooms' => 'nullable|integer',
            'units.*.The_number_of_halls' => 'nullable|integer',
            'units.*.The_number_of_kitchens' => 'nullable|integer',
            'units.*.The_number_of_toilets' => 'nullable|integer',
            'units.*.window_ac' => 'nullable|integer',
            'units.*.split_ac' => 'nullable|integer',
            'units.*.electricity_meter_number' => 'nullable|string|max:255',
            'units.*.water_meter_number' => 'nullable|string|max:255',
            'units.*.kitchen_tank' => 'nullable|boolean',
            'units.*.furnished' => 'nullable|boolean',
            'units.*.type_furnished' => \App\Support\TypeFurnished::rules(),
            'units.*.electricity_meter' => 'nullable|boolean',
            'units.*.water_meter' => 'nullable|boolean',
            'units.*.electricity_meter_ownership' => 'nullable|in:owner,tenant',
            'units.*.water_meter_ownership' => 'nullable|in:owner,tenant',
            'units.*.Number_parking_spaces' => 'nullable|string|max:255',
        ]);

        $realEstate = RealEstate::query()
            ->where('user_id', $userId)
            ->findOrFail((int) $request->real_estates_units_id);

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

        return response()->json([
            'message' => trans('api.created_success'),
            'code' => 201,
            'success' => true,
            'data' => [
                'created' => UnitResource::collection(collect($units)),
                'created_count' => count($units),
                'units' => UnitResource::collection($allUnits),
                'units_count' => $allUnits->count(),
            ],
        ], 201);
    }

    /**
     * @param  array<string, mixed>  $unit
     * @return array<string, mixed>
     */
    private function normalizeUnitItemArray(array $unit): array
    {
        if (array_key_exists('type_furnished', $unit)) {
            $unit['type_furnished'] = \App\Support\TypeFurnished::normalize($unit['type_furnished']);
        }

        foreach (['kitchen_tank', 'furnished', 'electricity_meter', 'water_meter'] as $key) {
            if (! array_key_exists($key, $unit)) {
                continue;
            }
            $value = $unit[$key];
            if ($value === '' || $value === null) {
                $unit[$key] = null;
                continue;
            }
            if (is_bool($value) || is_int($value)) {
                continue;
            }
            if (is_string($value)) {
                $trimmed = strtolower(trim($value));
                if (in_array($trimmed, ['0', '1'], true)) {
                    $unit[$key] = (int) $trimmed;
                } elseif (in_array($trimmed, ['true', 'false'], true)) {
                    $unit[$key] = $trimmed === 'true' ? 1 : 0;
                }
            }
        }

        foreach (['electricity_meter_ownership', 'water_meter_ownership'] as $key) {
            if (! array_key_exists($key, $unit)) {
                continue;
            }
            $value = $unit[$key];
            $unit[$key] = ($value === '' || $value === null) ? null : $value;
        }

        return $unit;
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

