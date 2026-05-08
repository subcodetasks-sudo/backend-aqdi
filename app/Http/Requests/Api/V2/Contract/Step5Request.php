<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;

class Step5Request extends BaseApiV2Request
{
    protected function prepareForValidation(): void
    {
        $booleanKeys = ['kitchen_tank', 'furnished', 'type_furnished', 'electricity_meter', 'water_meter'];
        $normalizedBooleans = [];

        foreach ($booleanKeys as $key) {
            if (! $this->exists($key)) {
                continue;
            }

            $value = $this->input($key);

            if ($value === '' || $value === null) {
                $normalizedBooleans[$key] = null;
                continue;
            }

            if (is_bool($value) || is_int($value)) {
                $normalizedBooleans[$key] = $value;
                continue;
            }

            if (is_string($value)) {
                $trimmed = strtolower(trim($value));
                if (in_array($trimmed, ['0', '1'], true)) {
                    $normalizedBooleans[$key] = (int) $trimmed;
                    continue;
                }
                if (in_array($trimmed, ['true', 'false'], true)) {
                    $normalizedBooleans[$key] = $trimmed === 'true' ? 1 : 0;
                    continue;
                }
            }
        }

        $this->merge(array_merge([
            'kitchen_tank' => 0,
            'furnished' => 0,
            'type_furnished' => 0,
            'electricity_meter' => 0,
            'water_meter' => 0,
        ], $normalizedBooleans));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:contracts,id',
            'unit_type_id' => 'required|exists:unit_types,id',
            'unit_usage_id' => 'required|exists:unit_usages,id',
            'unit_number' => 'required|string|max:255',
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
            'notes_edits' => 'nullable|string|max:20000',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف العقد مطلوب.',
            'id.exists' => 'العقد المحدد غير موجود.',
            'unit_type_id.required' => 'نوع الوحدة مطلوب.',
            'unit_usage_id.required' => 'استخدام الوحدة مطلوب.',
            'unit_number.required' => 'رقم الوحدة مطلوب.',
            'floor_number.required' => 'رقم الطابق مطلوب.',
            'unit_area.required' => 'مساحة الوحدة مطلوبة.',
            'window_ac.required' => 'عدد مكيفات الشباك مطلوب.',
            'split_ac.required' => 'عدد مكيفات السبليت مطلوب.',
        ];
    }
}