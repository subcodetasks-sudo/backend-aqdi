<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;

class UpdateContractRequest extends FormRequest
{
    /** Not updatable via this endpoint. */
    private const EXCLUDED_COLUMNS = [
        'id',
        'uuid',
        'created_at',
        'updated_at',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (self::EXCLUDED_COLUMNS as $key) {
            $this->request->remove($key);
        }
    }

    /**
     * All contract columns: optional partial update (send only fields to change).
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        if (! Schema::hasTable('contracts')) {
            return $rules;
        }

        foreach (Schema::getColumnListing('contracts') as $column) {
            if (in_array($column, self::EXCLUDED_COLUMNS, true)) {
                continue;
            }

            if ($column === 'tenant_role_ids') {
                $rules['tenant_role_ids'] = ['sometimes', 'nullable', 'array'];
                $rules['tenant_role_ids.*'] = ['nullable'];

                continue;
            }

            $rules[$column] = ['sometimes', 'nullable'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $payloadKeys = collect(array_keys($this->all()))
                ->reject(fn (string $key) => in_array($key, self::EXCLUDED_COLUMNS, true)
                    || str_contains($key, '.'))
                ->values();

            if ($payloadKeys->isEmpty()) {
                $validator->errors()->add(
                    'payload',
                    trans('api.contract_update_requires_field')
                );
            }
        });
    }

    /**
     * Columns allowed on contracts table (for mass update).
     *
     * @return list<string>
     */
    public static function updatableColumns(): array
    {
        if (! Schema::hasTable('contracts')) {
            return [];
        }

        return array_values(array_diff(
            Schema::getColumnListing('contracts'),
            self::EXCLUDED_COLUMNS
        ));
    }
}
