<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::rulesForKeys($this->presentUpdatableKeys());
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    public static function rulesForKeys(array $keys): array
    {
        $rules = [];

        foreach ($keys as $column) {
            if ($column === 'tenant_role_ids') {
                $rules['tenant_role_ids'] = ['nullable', 'array'];
                $rules['tenant_role_ids.*'] = ['nullable'];

                continue;
            }

            $rules[$column] = ['nullable'];
        }

        return $rules;
    }

    /**
     * Keys sent by client that map to contracts table columns.
     *
     * @return list<string>
     */
    public function presentUpdatableKeys(): array
    {
        $allowed = array_flip(self::updatableColumns());

        return collect(array_keys($this->all()))
            ->filter(fn (string $key) => ! in_array($key, self::EXCLUDED_COLUMNS, true)
                && ! str_contains($key, '.')
                && isset($allowed[$key]))
            ->values()
            ->all();
    }

    /**
     * Build payload from raw input (form-data or JSON), then validate.
     *
     * @return array<string, mixed>
     */
    public function updatePayload(): array
    {
        $keys = $this->presentUpdatableKeys();

        if ($keys === []) {
            throw ValidationException::withMessages([
                'payload' => [trans('api.contract_update_requires_field')],
            ]);
        }

        $payload = [];
        foreach ($keys as $key) {
            $payload[$key] = $this->input($key);
        }

        ValidatorFacade::make($payload, self::rulesForKeys($keys))->validate();

        return $payload;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->presentUpdatableKeys() === []) {
                $validator->errors()->add(
                    'payload',
                    trans('api.contract_update_requires_field')
                );
            }
        });
    }

    /**
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
