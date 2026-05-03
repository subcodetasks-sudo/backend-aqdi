<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\UnitResource;
use App\Http\Traits\Responser;
use App\Models\RealEstate;
use App\Models\UnitsReal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnitEstateController extends Controller
{
    use Responser;

    private function normalizeUnitBooleanFlags(Request $request): void
    {
        $keys = ['kitchen_tank', 'furnished', 'type_furnished', 'electricity_meter', 'water_meter'];
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

        if ($normalized !== []) {
            $request->merge($normalized);
        }
    }

    public function index($id)
    {
        $userReal = RealEstate::findOrFail($id);
        $user = Auth::user();
        $units = UnitsReal::where('real_estates_units_id', $userReal->id)->where('user_id', $user->id)->get();

        return $this->apiResponse(UnitResource::collection($units), trans('api.units'));
    }

    public function all($id)
    {
        $user = Auth::user();

        try {
            $userReal = RealEstate::findOrFail($id);
            $units = UnitsReal::where('real_estates_units_id', $userReal->id)
                ->where('user_id', $user->id)
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
        $this->normalizeUnitBooleanFlags($request);

        $rules = [
            'real_estates_units_id' => 'required|exists:real_estates,id',
            'unit_type_id' => 'required|exists:unit_types,id',
            'unit_usage_id' => 'required|exists:unit_usages,id',
            'unit_number' => 'required|string',
            'floor_number' => 'required|integer',
            'unit_area' => 'required|numeric',
            'tootal_rooms' => 'nullable|integer',
            'The_number_of_halls' => 'nullable|integer',
            'The_number_of_kitchens' => 'nullable|integer',
            'The_number_of_toilets' => 'nullable|integer',
            'window_ac' => 'required|integer',
            'split_ac' => 'required|integer',
            'electricity_meter_number' => 'nullable|string|max:255',
            'water_meter_number' => 'nullable|string|max:255',
            'kitchen_tank' => 'nullable|boolean',
            'furnished' => 'nullable|boolean',
            'type_furnished' => 'nullable|boolean',
            'electricity_meter' => 'nullable|boolean',
            'water_meter' => 'nullable|boolean',
        ];

        $this->validate($request, $rules);
        $user = auth()->user();

        $data = [
            'real_estates_units_id' => $request->real_estates_units_id,
            'unit_type_id' => $request->unit_type_id,
            'unit_usage_id' => $request->unit_usage_id,
            'unit_number' => $request->unit_number,
            'floor_number' => $request->floor_number,
            'unit_area' => $request->unit_area,
            'tootal_rooms' => $request->tootal_rooms,
            'The_number_of_halls' => $request->The_number_of_halls,
            'The_number_of_kitchens' => $request->The_number_of_kitchens,
            'The_number_of_toilets' => $request->The_number_of_toilets,
            'window_ac' => $request->window_ac,
            'split_ac' => $request->split_ac,
            'electricity_meter_number' => $request->electricity_meter_number,
            'water_meter_number' => $request->water_meter_number,
            'kitchen_tank' => $request->boolean('kitchen_tank'),
            'furnished' => $request->boolean('furnished'),
            'type_furnished' => $request->boolean('type_furnished'),
            'electricity_meter' => $request->boolean('electricity_meter'),
            'water_meter' => $request->boolean('water_meter'),
            'user_id' => $user->id,
        ];

        $realEstateUnit = UnitsReal::create($data);

        return response()->json([
            'message' => trans('api.created_success'),
            'code' => 201,
            'success' => true,
            'data' => new UnitResource($realEstateUnit),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->normalizeUnitBooleanFlags($request);

        $rules = [
            'real_estates_units_id' => 'sometimes|exists:real_estates,id',
            'unit_type_id' => 'sometimes|exists:unit_types,id',
            'unit_usage_id' => 'sometimes|exists:unit_usages,id',
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
            'type_furnished' => 'sometimes|boolean',
            'electricity_meter' => 'sometimes|boolean',
            'water_meter' => 'sometimes|boolean',
        ];

        $this->validate($request, $rules);
        $units = UnitsReal::findOrFail($id);

        $data = $request->only([
            'unit_type_id',
            'unit_usage_id',
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
        ]);

        $data['user_id'] = auth()->id();

        foreach (['kitchen_tank', 'furnished', 'type_furnished', 'electricity_meter', 'water_meter'] as $flag) {
            if ($request->exists($flag)) {
                $data[$flag] = (int) $request->boolean($flag);
            }
        }

        try {
            $units->update($data);
            return $this->apiResponse(new UnitResource($units->fresh()), trans('api.success'), 200);
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

