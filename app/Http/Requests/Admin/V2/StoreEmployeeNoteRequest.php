<?php

namespace App\Http\Requests\Admin\V2;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'addition_date' => ['required', 'date'],
            'note' => ['required', 'string', 'max:10000'],
        ];
    }
}
