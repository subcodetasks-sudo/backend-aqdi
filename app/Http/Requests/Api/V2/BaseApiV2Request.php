<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseApiV2Request extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        app()->setLocale('ar');

        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first() ?: 'البيانات المدخلة غير صحيحة.',
            'code' => 422,
            'success' => false,
        ], 422));
    }
}

