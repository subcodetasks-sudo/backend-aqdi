<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Http\Requests\Api\V2\Concerns\NormalizesCoordinateInputs;
use App\Http\Requests\Api\V2\Concerns\ResolvesContractIdInput;
use App\Models\Contract;
use Illuminate\Validation\Rule;

class Step2Request extends BaseApiV2Request
{
    use NormalizesCoordinateInputs;
    use ResolvesContractIdInput;

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->resolveContractIdInput();
        $this->normalizeCoordinateInputs();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $skipStepTwo = $this->shouldSkipStepTwo();

        return [
            'id' => 'required|exists:contracts,id',
            // 'property_place_id' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'integer', 'exists:regions,id'],
            // 'property_city_id' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'integer', 'exists:cities,id'],
            // 'neighborhood' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'string', 'max:255'],
            // 'street' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'string', 'max:255'],
            // 'building_number' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'string', 'max:50'],
            // 'postal_code' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'string', 'max:20'],
            // 'extra_figure' => [Rule::requiredIf(! $skipStepTwo), 'nullable', 'string', 'max:255'],
            'address_url' => 'nullable|string|max:2048',
            'image_address' => 'nullable|image',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ];
    }

    private function shouldSkipStepTwo(): bool
    {
        $contractId = $this->input('id');
        if (! $contractId) {
            return false;
        }

        $instrumentType = Contract::query()->whereKey($contractId)->value('instrument_type');

        return Contract::shouldSkipInitialSteps($instrumentType);
    }

    public function messages(): array
    {
        return $this->contractV2ArabicMessages([
            'id',
            'property_place_id',
            'property_city_id',
            'neighborhood',
            'street',
            'building_number',
            'postal_code',
            'extra_figure',
            'address_url',
            'image_address',
            'latitude',
            'longitude',
            'lat',
            'lng',
        ]);
    }
}

