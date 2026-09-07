<?php

namespace App\Modules\Catalog\Requests\Admin;


class SearchUnitTypeRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'required|string|min:1',
        ];
    }
}
