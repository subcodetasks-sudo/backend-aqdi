<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Http\Requests\Api\V2\Concerns\ResolvesContractIdInput;
use App\Models\Contract;
use App\Support\TypeFurnished;

class Step5Request extends BaseApiV2Request
{
    use ResolvesContractIdInput;

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->resolveContractIdInput();

        if ($this->exists('type_furnished')) {
            $this->merge([
                'type_furnished' => TypeFurnished::normalize($this->input('type_furnished')),
            ]);
        }

        $booleanKeys = ['kitchen_tank', 'furnished', 'electricity_meter', 'water_meter'];
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
        if ($this->isSubleaseAgreementContract()) {
            return $this->subleaseAgreementRules();
        }

        return [
            'id' => 'required|exists:contracts,id',
            'unit_type_id' => 'required|exists:unit_types,id',
            'unit_usage_id' => 'nullable|exists:unit_usages,id',
            'unit_number' => 'required|string|max:255',
            'floor_number' => 'required|integer',
            'unit_area' => 'required|numeric',
            'tootal_rooms' => 'nullable|integer',
            'The_number_of_halls' => 'nullable|integer',
            'number_of_councils' => 'nullable|integer',
            'The_number_of_kitchens' => 'nullable|integer',
            'The_number_of_the_toilet' => 'nullable|integer',
            'window_ac' => 'nullable|integer',
            'split_ac' => 'nullable|integer',
            'electricity_meter_number' => 'nullable|string|max:255',
            'water_meter_number' => 'nullable|string|max:255',
            'kitchen_tank' => 'nullable|boolean',
            'furnished' => 'nullable|boolean',
            'type_furnished' => \App\Support\TypeFurnished::rules(),
            'electricity_meter' => 'nullable|boolean',
            'water_meter' => 'nullable|boolean',
        ];
    }

    private function subleaseAgreementRules(): array
    {
        return [
            'id' => 'required|exists:contracts,id',
            'unit_type_id' => 'nullable|exists:unit_types,id',
            'unit_usage_id' => 'nullable|exists:unit_usages,id',
            'unit_number' => 'required|string|max:255',
            'floor_number' => 'required|integer',
            'unit_area' => 'required|numeric',
            'tootal_rooms' => 'nullable|integer',
            'The_number_of_halls' => 'nullable|integer',
            'number_of_councils' => 'nullable|integer',
            'The_number_of_kitchens' => 'nullable|integer',
            'The_number_of_the_toilet' => 'nullable|integer',
            'window_ac' => 'nullable|integer',
            'split_ac' => 'nullable|integer',
            'electricity_meter_number' => 'nullable|string|max:255',
            'water_meter_number' => 'nullable|string|max:255',
            'kitchen_tank' => 'nullable|boolean',
            'furnished' => 'nullable|boolean',
            'type_furnished' => \App\Support\TypeFurnished::rules(),
            'electricity_meter' => 'nullable|boolean',
            'water_meter' => 'nullable|boolean',
        ];
    }

    private function isSubleaseAgreementContract(): bool
    {
        $contractId = $this->input('id');

        return $contractId
            && Contract::query()->whereKey($contractId)->value('instrument_type') === 'sublease_agreement';
    }

    public function messages(): array
    {
        return $this->contractV2ArabicMessages([
            'id',
            'unit_type_id',
            'unit_usage_id',
            'unit_number',
            'floor_number',
            'unit_area',
            'tootal_rooms',
            'The_number_of_halls',
            'number_of_councils',
            'The_number_of_kitchens',
            'The_number_of_the_toilet',
            'window_ac',
            'split_ac',
            'electricity_meter_number',
            'water_meter_number',
            'kitchen_tank',
            'furnished',
            'type_furnished',
            'electricity_meter',
            'water_meter',
        ]);
    }
}